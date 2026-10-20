<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\PushNotificationServiceInterface;
use App\DTOs\PushDeliveryResultDTO;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Exception\MessagingException;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Throwable;

class FirebasePushService implements PushNotificationServiceInterface
{
    public function __construct(
        private readonly Messaging $messaging
    ) {}

    /**
     * {@inheritDoc}
     */
    public function sendToToken(
        string $token,
        string $title,
        string $body,
        array $data = [],
        ?string $imageUrl = null
    ): PushDeliveryResultDTO {
        return $this->sendMulticast([$token], $title, $body, $data, $imageUrl);
    }

    /**
     * {@inheritDoc}
     */
    public function sendMulticast(
        array $tokens,
        string $title,
        string $body,
        array $data = [],
        ?string $imageUrl = null
    ): PushDeliveryResultDTO {
        $validTokens = array_values(array_filter(array_unique($tokens)));

        if ($validTokens === []) {
            return new PushDeliveryResultDTO;
        }

        try {
            $notification = Notification::create($title, $body, $imageUrl);

            $message = CloudMessage::new()
                ->withNotification($notification)
                ->withData($this->sanitizePayloadData($data));

            $report = $this->messaging->sendMulticast($message, $validTokens);

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
        string $title,
        string $body,
        array $data = [],
        ?string $imageUrl = null
    ): PushDeliveryResultDTO {
        try {
            $notification = Notification::create($title, $body, $imageUrl);

            $message = CloudMessage::new()
                ->withTopic($topic)
                ->withNotification($notification)
                ->withData($this->sanitizePayloadData($data));

            $this->messaging->send($message);

            return new PushDeliveryResultDTO(successCount: 1, failureCount: 0);
        } catch (MessagingException|Throwable $e) {
            Log::error('Firebase Topic Push Error: '.$e->getMessage(), [
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
    public function subscribeToTopics(array $topics, array|string $tokens): bool
    {
        $cleanTopics = array_values(array_filter(array_unique($topics)));
        if ($cleanTopics === []) {
            return true;
        }

        try {
            $this->messaging->subscribeToTopics($cleanTopics, $tokens);

            return true;
        } catch (MessagingException|Throwable $e) {
            Log::warning('Firebase Subscribe To Topics Error: '.$e->getMessage(), [
                'topics' => $cleanTopics,
            ]);

            return false;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function unsubscribeFromTopics(array $topics, array|string $tokens): bool
    {
        $cleanTopics = array_values(array_filter(array_unique($topics)));
        if ($cleanTopics === []) {
            return true;
        }

        try {
            $this->messaging->unsubscribeFromTopics($cleanTopics, $tokens);

            return true;
        } catch (MessagingException|Throwable $e) {
            Log::warning('Firebase Unsubscribe From Topics Error: '.$e->getMessage(), [
                'topics' => $cleanTopics,
            ]);

            return false;
        }
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
