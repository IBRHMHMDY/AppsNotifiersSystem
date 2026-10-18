<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Contracts\PushNotificationServiceInterface;
use App\Enums\NotificationStatus;
use App\Models\AppNotification;
use App\Models\Device;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
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
     * تنفيذ إرسال الإشعار عبر الواجهة المعزولة (PushNotificationServiceInterface).
     */
    public function handle(PushNotificationServiceInterface $pushService): void
    {
        $notification = $this->appNotification->fresh(['clientApp', 'media']);

        if (! $notification || ! $notification->clientApp) {
            return;
        }

        // استخراج رابط الصورة من مجموعة Spatie Media Library إن وجدت
        $imageUrl = $notification->getFirstMediaUrl('notification_image') ?: null;

        $totalSuccess = 0;
        $totalFailure = 0;
        $invalidTokensToClean = [];

        // جلب الأجهزة المرتبطة بالتطبيق مع فلترة نظام التشغيل المستهدف (target_os) إن تم تحديده
        Device::query()
            ->where('client_app_id', $notification->client_app_id)
            ->whereNotNull('fcm_token')
            ->when(
                $notification->target_os !== null,
                fn ($query) => $query->where('os_type', $notification->target_os)
            )
            ->chunkById(500, function (Collection $devices) use (
                $pushService,
                $notification,
                $imageUrl,
                &$totalSuccess,
                &$totalFailure,
                &$invalidTokensToClean
            ): void {
                /** @var array<int, string> $tokens */
                $tokens = $devices->pluck('fcm_token')->filter()->values()->all();

                $result = $pushService->sendMulticast(
                    tokens: $tokens,
                    title: (string) $notification->title,
                    body: (string) $notification->body,
                    data: [
                        'notification_id' => (string) $notification->id,
                        'client_app_id' => (string) $notification->client_app_id,
                    ],
                    imageUrl: $imageUrl,
                );

                $totalSuccess += $result->successCount;
                $totalFailure += $result->failureCount;

                if ($result->invalidTokens !== []) {
                    $invalidTokensToClean = array_merge($invalidTokensToClean, $result->invalidTokens);
                }
            });

        // تنظيف التوكنات غير الصالحة أو المنتهية من جدول الأجهزة تلقائياً
        if ($invalidTokensToClean !== []) {
            Device::query()
                ->where('client_app_id', $notification->client_app_id)
                ->whereIn('fcm_token', array_unique($invalidTokensToClean))
                ->delete();
        }

        // تحديد الحالة النهائية للإشعار وتوثيق وقت الإرسال الفعلي (sent_at)
        $finalStatus = ($totalSuccess > 0 || $totalFailure === 0)
            ? NotificationStatus::SENT
            : NotificationStatus::FAILED;

        $notification->update([
            'status' => $finalStatus,
            'sent_at' => now(),
        ]);
    }

    /**
     * معالجة الفشل النهائي بعد استنفاد جميع المحاولات.
     */
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