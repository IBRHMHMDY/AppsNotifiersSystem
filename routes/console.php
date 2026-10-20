<?php

declare(strict_types=1);

use App\Actions\Notifications\DispatchNotificationAction;
use App\Enums\NotificationStatus;
use App\Models\AppNotification;
use Illuminate\Support\Facades\Schedule;

Schedule::call(function (DispatchNotificationAction $dispatchAction): void {
    AppNotification::query()
        ->where('status', NotificationStatus::PENDING)
        ->whereNotNull('scheduled_at')
        ->where('scheduled_at', '<=', now())
        ->chunkById(100, function ($notifications) use ($dispatchAction): void {
            foreach ($notifications as $notification) {
                $dispatchAction->execute($notification);
            }
        });
})
    ->everyMinute()
    ->name('dispatch-scheduled-app-notifications')
    ->withoutOverlapping();
