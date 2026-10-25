<?php

declare(strict_types=1);

namespace App\Actions\Notifications;

use App\Enums\NotificationStatus;
use App\Jobs\ProcessAppNotificationJob;
use App\Models\AppNotification;

class DispatchNotificationAction
{
    /**
     * دفع الإشعار إلى طابور المعالجة (فوراً أو بناءً على وقت الجدولة).
     */
    public function execute(AppNotification $notification, bool $forceNow = false): void
    {
        if (! in_array($notification->status, [NotificationStatus::PENDING, NotificationStatus::FAILED], true)) {
            return;
        }

        if ($forceNow || $notification->scheduled_at === null || $notification->scheduled_at->isPast()) {
            if ($forceNow && $notification->scheduled_at !== null && $notification->scheduled_at->isFuture()) {
                $notification->update(['scheduled_at' => now()]);
            }

            ProcessAppNotificationJob::dispatch($notification);

            return;
        }

        ProcessAppNotificationJob::dispatch($notification)
            ->delay($notification->scheduled_at);
    }
}