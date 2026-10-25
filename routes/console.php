<?php

declare(strict_types=1);

use App\Actions\Notifications\DispatchNotificationAction;
use App\Enums\NotificationStatus;
use App\Models\AppNotification;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('notifications:dispatch-scheduled', function (): void {
    $dispatchAction = app(DispatchNotificationAction::class);
    $count = 0;

    AppNotification::query()
        ->where('status', NotificationStatus::PENDING)
        ->whereNotNull('scheduled_at')
        ->where('scheduled_at', '<=', now())
        ->chunkById(100, function ($notifications) use ($dispatchAction, &$count): void {
            foreach ($notifications as $notification) {
                $dispatchAction->execute($notification);
                $count++;
            }
        });

    $this->info("Dispatched {$count} scheduled notifications.");
})->purpose('Dispatch due scheduled app notifications');

Schedule::command('notifications:dispatch-scheduled')
    ->everyMinute()
    ->withoutOverlapping();
