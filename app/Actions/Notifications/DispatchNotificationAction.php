<?php

declare(strict_types=1);

namespace App\Actions\Notifications;

use App\Enums\NotificationStatus;
use App\Models\AppNotification;
use App\Services\FirebasePushService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class DispatchNotificationAction
{
    public function __construct(
        private readonly FirebasePushService $firebaseService
    ) {}

    /**
     * تنفيذ إرسال الإشعار المجدول أو المباشر.
     */
    public function execute(AppNotification $notification): void
    {
        // 1. تحديث الحالة إلى "قيد المعالجة"
        $notification->update(['status' => NotificationStatus::PROCESSING]);

        // 2. بناء استعلام جلب الأجهزة بناءً على إعدادات الإشعار
        $query = \App\Models\Device::query();

        // هل الإشعار مخصص لتطبيق معين؟
        if ($notification->client_app_id) {
            $query->where('client_app_id', $notification->client_app_id);
        }

        // هل الإشعار مخصص لنظام تشغيل معين (Android/iOS)؟
        if ($notification->target_os) {
            $query->where('os_type', $notification->target_os);
        }

        // 3. تقسيم الأجهزة لتدفقات (Chunks) بحد أقصى 500 توكن (الحد الأقصى لـ Firebase Multicast)
        $successCount = 0;
        
        $query->whereNotNull('fcm_token')->chunk(500, function ($devices) use ($notification, &$successCount) {
            $tokens = $devices->pluck('fcm_token')->toArray();
            
            // رابط الصورة من Spatie Media Library (إن وُجدت)
            $imageUrl = $notification->getFirstMediaUrl('notification_image');

            $report = $this->firebaseService->sendMulticast(
                $tokens,
                $notification->title,
                $notification->body,
                $imageUrl ?: null
            );

            $successCount += $report['success'];
        });

        // 4. تحديث حالة الإشعار بعد الانتهاء
        $notification->update([
            'status' => $successCount > 0 ? NotificationStatus::SENT : NotificationStatus::FAILED,
            'sent_at' => now(),
        ]);
    }
}