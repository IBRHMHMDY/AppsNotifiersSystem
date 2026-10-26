<?php

// app/Services/FirebasePushService.php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\PushNotificationServiceInterface;
use App\DTOs\PushDeliveryResultDTO;
use App\Models\ClientApp;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Exception\MessagingException;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Throwable;

class FirebasePushService implements PushNotificationServiceInterface
{
    /**
     * تخزين مؤقت لاتصالات Firebase المخصصة لكل تطبيق خلال دورة حياة الـ Worker.
     *
     * @var array<int, Messaging>
     */
    private array $customMessagingInstances = [];

    public function __construct(
        private readonly Messaging $messaging
    ) {}

    /**
     * {@inheritDoc}
     */
    public function sendToToken(
        string $token,
        ?string $title,
        ?string $body,
        array $data = [],
        ?string $imageUrl = null,
        ?ClientApp $clientApp = null
    ): PushDeliveryResultDTO {
        return $this->sendMulticast([$token], $title, $body, $data, $imageUrl, $clientApp);
    }

    /**
     * {@inheritDoc}
     */
    public function sendMulticast(
        array $tokens,
        ?string $title,
        ?string $body,
        array $data = [],
        ?string $imageUrl = null,
        ?ClientApp $clientApp = null
    ): PushDeliveryResultDTO {
        $validTokens = array_values(array_filter(array_unique($tokens)));

        if ($validTokens === []) {
            return new PushDeliveryResultDTO;
        }

        try {
            $messaging = $this->resolveMessaging($clientApp);

            $message = CloudMessage::new()
                ->withData($this->sanitizePayloadData($data));

            if ($this->shouldAttachNotificationBlock($title, $body)) {
                $message = $message->withNotification(
                    Notification::create((string) $title, (string) $body, $imageUrl)
                );
            }

            $report = $messaging->sendMulticast($message, $validTokens);

            $invalidTokens = array_values(array_unique(array_merge(
                $report->unknownTokens(),
                $report->invalidTokens()
            )));

            $errors = [];
            if ($report->hasFailures()) {
                foreach ($report->failures()->getItems() as $failure) {
                    $errors[] = $failure->error()?->getMessage() ?? 'Unknown FCM multicast error';
                }
            }

            return new PushDeliveryResultDTO(
                successCount: $report->successes()->count(),
                failureCount: $report->failures()->count(),
                invalidTokens: $invalidTokens,
                errors: array_slice(array_unique($errors), 0, 10),
            );
        } catch (MessagingException|Throwable $e) {
            Log::error('Firebase Multicast Push Error: '.$e->getMessage(), [
                'client_app_id' => $clientApp?->id,
                'tokens_count' => count($validTokens),
                'exception' => $e,
            ]);

            return new PushDeliveryResultDTO(
                successCount: 0,
                failureCount: count($validTokens),
                invalidTokens: [],
                errors: [$e->getMessage()],
            );
        }
    }

    /**
     * {@inheritDoc}
     */
    public function sendToTopic(
        string $topic,
        ?string $title,
        ?string $body,
        array $data = [],
        ?string $imageUrl = null,
        ?ClientApp $clientApp = null
    ): PushDeliveryResultDTO {
        try {
            $messaging = $this->resolveMessaging($clientApp);

            $message = CloudMessage::new()
                ->withTopic($topic)
                ->withData($this->sanitizePayloadData($data));

            if ($this->shouldAttachNotificationBlock($title, $body)) {
                $message = $message->withNotification(
                    Notification::create((string) $title, (string) $body, $imageUrl)
                );
            }

            $messaging->send($message);

            return new PushDeliveryResultDTO(successCount: 1, failureCount: 0);
        } catch (MessagingException|Throwable $e) {
            Log::error('Firebase Topic Push Error: '.$e->getMessage(), [
                'client_app_id' => $clientApp?->id,
                'topic' => $topic,
                'exception' => $e,
            ]);

            return new PushDeliveryResultDTO(
                successCount: 0,
                failureCount: 1,
                errors: [$e->getMessage()],
            );
        }
    }

    /**
     * {@inheritDoc}
     */
    public function subscribeToTopics(
        array $topics,
        array|string $tokens,
        ?ClientApp $clientApp = null
    ): bool {
        $cleanTopics = array_values(array_filter(array_unique($topics)));
        if ($cleanTopics === []) {
            return true;
        }

        try {
            $this->resolveMessaging($clientApp)->subscribeToTopics($cleanTopics, $tokens);

            return true;
        } catch (MessagingException|Throwable $e) {
            Log::warning('Firebase Subscribe To Topics Error: '.$e->getMessage(), [
                'client_app_id' => $clientApp?->id,
                'topics' => $cleanTopics,
            ]);

            return false;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function unsubscribeFromTopics(
        array $topics,
        array|string $tokens,
        ?ClientApp $clientApp = null
    ): bool {
        $cleanTopics = array_values(array_filter(array_unique($topics)));
        if ($cleanTopics === []) {
            return true;
        }

        try {
            $this->resolveMessaging($clientApp)->unsubscribeFromTopics($cleanTopics, $tokens);

            return true;
        } catch (MessagingException|Throwable $e) {
            Log::warning('Firebase Unsubscribe From Topics Error: '.$e->getMessage(), [
                'client_app_id' => $clientApp?->id,
                'topics' => $cleanTopics,
            ]);

            return false;
        }
    }

    /**
     * اختيار كائن Messaging المناسب (الخاص بالتطبيق إن وُجد، أو الافتراضي للنظام).
     */
    private function resolveMessaging(?ClientApp $clientApp): Messaging
    {
        if (! $clientApp instanceof ClientApp || ! $clientApp->hasCustomFirebaseCredentials()) {
            return $this->messaging;
        }

        $appId = (int) $clientApp->id;

        if (! isset($this->customMessagingInstances[$appId])) {
            /** @var array<string, mixed> $credentials */
            $credentials = $clientApp->firebase_credentials;

            $this->customMessagingInstances[$appId] = (new Factory)
                ->withServiceAccount($credentials)
                ->createMessaging();
        }

        return $this->customMessagingInstances[$appId];
    }

    /**
     * التحقق مما إذا كان الإشعار مرئياً ويحتاج إلى Notification Block أم صامتاً (Data-Only).
     */
    private function shouldAttachNotificationBlock(?string $title, ?string $body): bool
    {
        return ($title !== null && trim($title) !== '')
            || ($body !== null && trim($body) !== '');
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, string>
     */
    private function sanitizePayloadData(array $data): array
    {
        $sanitized = [];

        foreach ($data as $key => $value) {
            $sanitized[(string) $key] = is_scalar($value)
                ? (string) $value
                : (json_encode($value) ?: '');
        }

        return $sanitized;
    }
}
