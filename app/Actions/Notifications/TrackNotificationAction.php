<?php

declare(strict_types=1);

namespace App\Actions\Notifications;

use App\DTOs\TrackNotificationDTO;
use App\Enums\NotificationStatus;
use App\Models\AppNotification;
use App\Models\Device;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

class TrackNotificationAction
{
    /**
     * تسجيل حدث التتبع وتحديث العدادات الذرية وحالة الإشعار بأمان.
     */
    public function execute(TrackNotificationDTO $dto): bool
    {
        /** @var AppNotification|null $notification */
        $notification = AppNotification::query()
            ->whereKey($dto->notificationId)
            ->where(function (Builder $query) use ($dto): void {
                $query->whereNull('client_app_id')
                    ->orWhere('client_app_id', $dto->clientAppId);
            })
            ->first();

        if (! $notification) {
            return false;
        }

        // منع تكرار احتساب نفس الحدث لنفس الجهاز (Idempotency) في حال توفر fcm_token
        if ($dto->fcmToken !== null && $dto->fcmToken !== '') {
            $deduplicationKey = sprintf(
                'notif_track:%d:%s:%s',
                $notification->id,
                $dto->event->value,
                hash('sha256', $dto->fcmToken)
            );

            // إذا تم تسجيل هذا الحدث مسبقاً لهذا الجهاز خلال 7 أيام، نتجاهل الزيادة المكررة
            if (! Cache::add($deduplicationKey, 1, now()->addDays(7))) {
                return true;
            }

            // تحديث وقت آخر نشاط للجهاز
            Device::query()
                ->where('client_app_id', $dto->clientAppId)
                ->where('fcm_token', $dto->fcmToken)
                ->update(['last_active_at' => now()]);
        }

        // تحديث العداد الذري (Atomic Increment) وحالة الإشعار إذا لزم الأمر
        $extraUpdates = [];
        if ($notification->status !== NotificationStatus::SENT) {
            $extraUpdates['status'] = NotificationStatus::SENT;
            $extraUpdates['sent_at'] = $notification->sent_at ?? now();
        }

        $notification->increment($dto->event->counterColumn(), 1, $extraUpdates);

        return true;
    }
}