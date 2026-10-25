<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Contracts\PushNotificationServiceInterface;
use App\Enums\NotificationActionType;
use App\Enums\NotificationStatus;
use App\Enums\NotificationTargetType;
use App\Enums\NotificationType;
use App\Models\AppNotification;
use App\Models\Device;
use App\Services\TargetAudienceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessAppNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 30;

    public function __construct(
        public readonly AppNotification $appNotification
    ) {}

    /**
     * تنفيذ إرسال الإشعار (سواء عبر Topic أو عبر دفعات Multicast 500 جهاز).
     */
    public function handle(
        PushNotificationServiceInterface $pushService,
        TargetAudienceService $audienceService
    ): void {
        $notification = $this->appNotification->fresh(['clientApp', 'media']);

        if (! $notification) {
            return;
        }

        // إذا كان الإشعار مرتبطاً بتطبيق محدد وكان هذا التطبيق غير مفعل، نلغي الإرسال
        if ($notification->client_app_id !== null && ! $notification->clientApp?->is_active) {
            $notification->update(['status' => NotificationStatus::FAILED]);

            return;
        }

        $notificationType = $notification->type ?? NotificationType::General;
        $targetType = $notification->target_type ?? NotificationTargetType::AllDevices;
        $actionType = $notification->action_type ?? NotificationActionType::OpenApp;

        $isSilent = $notificationType->isSilent();
        $title = $isSilent ? null : (string) $notification->title;
        $body = $isSilent ? null : (string) $notification->body;
        $imageUrl = $isSilent ? null : ($notification->getFirstMediaUrl('notification_image') ?: null);

        $customData = is_array($notification->payload_data) ? $notification->payload_data : [];
        $payload = array_merge($customData, [
            'notification_id' => (string) $notification->id,
            'client_app_id' => (string) ($notification->client_app_id ?? 'all'),
            'notification_type' => $notificationType->value,
            'action_type' => $actionType->value,
            'action_value' => (string) ($notification->action_value ?? ''),
            'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
        ]);

        // المسار الأول: إذا كان الاستهداف موجهاً إلى موضوع (Firebase Topic)
        $isTopicTarget = $targetType === NotificationTargetType::Topic || ! empty($notification->topic);

        if ($isTopicTarget && ! empty($notification->topic)) {
            $result = $pushService->sendToTopic(
                topic: trim((string) $notification->topic),
                title: $title,
                body: $body,
                data: $payload,
                imageUrl: $imageUrl,
            );

            $notification->update([
                'status' => $result->isCompletelySuccessful() ? NotificationStatus::SENT : NotificationStatus::FAILED,
                'sent_count' => $result->successCount,
                'failed_count' => $result->failureCount,
                'sent_at' => now(),
            ]);

            return;
        }

        // المسار الثاني: الإرسال المجمع (Batch Multicast) للأجهزة المستهدفة حسب الفلترة (500 جهاز في كل دفعة)
        $totalSuccess = 0;
        $totalFailure = 0;
        $invalidTokensToClean = [];

        $audienceService
            ->buildQueryForNotification($notification)
            ->chunkById(500, function (Collection $devices) use (
                $pushService,
                $title,
                $body,
                $payload,
                $imageUrl,
                &$totalSuccess,
                &$totalFailure,
                &$invalidTokensToClean
            ): void {
                /** @var array<int, string> $tokens */
                $tokens = $devices->pluck('fcm_token')->filter()->values()->all();

                $result = $pushService->sendMulticast(
                    tokens: $tokens,
                    title: $title,
                    body: $body,
                    data: $payload,
                    imageUrl: $imageUrl,
                );

                $totalSuccess += $result->successCount;
                $totalFailure += $result->failureCount;

                if ($result->invalidTokens !== []) {
                    $invalidTokensToClean = array_merge($invalidTokensToClean, $result->invalidTokens);
                }
            });

        // تنظيف التوكنات التالفة أو الملغاة تلقائياً على دفعات لتجنب تجاوز حدود SQL placeholders
        if ($invalidTokensToClean !== []) {
            foreach (array_chunk(array_unique($invalidTokensToClean), 500) as $tokenChunk) {
                Device::query()
                    ->when(
                        $notification->client_app_id !== null,
                        fn (Builder $q): Builder => $q->where('client_app_id', $notification->client_app_id)
                    )
                    ->whereIn('fcm_token', $tokenChunk)
                    ->delete();
            }
        }

        $finalStatus = ($totalSuccess > 0 || $totalFailure === 0)
            ? NotificationStatus::SENT
            : NotificationStatus::FAILED;

        $notification->update([
            'status' => $finalStatus,
            'sent_count' => $totalSuccess,
            'failed_count' => $totalFailure,
            'sent_at' => now(),
        ]);
    }

    public function failed(Throwable $exception): void
    {
        Log::error('ProcessAppNotificationJob failed permanently: '.$exception->getMessage(), [
            'notification_id' => $this->appNotification->id,
            'client_app_id' => $this->appNotification->client_app_id,
        ]);

        $this->appNotification->update([
            'status' => NotificationStatus::FAILED,
        ]);
    }
}