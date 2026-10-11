<?php

use App\Enums\NotificationStatus;
use App\Jobs\ProcessAppNotificationJob;
use App\Models\AppNotification;
use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Console Routes & Task Scheduling
|--------------------------------------------------------------------------
*/

// فحص الإشعارات المجدولة كل دقيقة
Schedule::call(function () {
    $notifications = AppNotification::where('status', NotificationStatus::PENDING)
        ->whereNotNull('scheduled_at')
        ->where('scheduled_at', '<=', now())
        ->get();

    foreach ($notifications as $notification) {
        // تحديث الحالة لمنع الإرسال المزدوج
        $notification->update(['status' => NotificationStatus::PROCESSING]);
        
        // إرسال المهمة للطابور
        ProcessAppNotificationJob::dispatch($notification);
    }
})->everyMinute()->name('dispatch-scheduled-notifications')->withoutOverlapping();