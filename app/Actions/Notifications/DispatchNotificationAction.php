<?php

declare(strict_types=1);

namespace App\Actions\Notifications;

use App\Enums\NotificationStatus;
use App\Jobs\ProcessAppNotificationJob;
use App\Models\AppNotification;
use Illuminate\Support\Facades\DB;

class DispatchNotificationAction
{
    /**
     * تحديث حالة الإشعار وجدولته للإرسال الفوري أو المستقبلي حسب حقل scheduled_at.
     */
    public function execute(AppNotification $notification): void
    {
        DB::transaction(function () use ($notification): void {
            $notification->update([
                'status' => NotificationStatus::PENDING,
            ]);
        });

        $pendingDispatch = ProcessAppNotificationJob::dispatch($notification)->afterCommit();

        // إذا كان الإشعار مجدولاً لوقت مستقبلي، يتم تأخير المهمة في الطابور حتى يحين موعدها
        if ($notification->scheduled_at !== null && $notification->scheduled_at->isFuture()) {
            $pendingDispatch->delay($notification->scheduled_at);
        }
    }
}