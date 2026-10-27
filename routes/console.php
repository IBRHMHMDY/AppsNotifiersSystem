<?php

// routes/console.php

declare(strict_types=1);

use App\Actions\Notifications\DispatchNotificationAction;
use App\Enums\NotificationStatus;
use App\Models\AppNotification;
use App\Models\Device;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('notifications:dispatch-scheduled', function (): void {
    $dispatchAction = app(DispatchNotificationAction::class);
    $count = 0;

    // 1. استعادة الإشعارات العالقة في حالة PROCESSING لأكثر من 30 دقيقة بسبب توقف مفاجئ في الـ Queue Worker
    AppNotification::query()
        ->where('status', NotificationStatus::PROCESSING)
        ->where('updated_at', '<=', now()->subMinutes(30))
        ->update(['status' => NotificationStatus::PENDING]);

    // 2. دفع الإشعارات المجدولة التي حان وقت إرسالها
    AppNotification::query()
        ->where('status', NotificationStatus::PENDING)
        ->whereNotNull('scheduled_at')
        ->where('scheduled_at', '<=', now())
        ->chunkById(100, function ($notifications) use ($dispatchAction, &$count): void {
            foreach ($notifications as $notification) {
                $dispatchAction->execute($notification, forceNow: true);
                $count++;
            }
        });

    $this->info("Dispatched {$count} scheduled notifications.");
})->purpose('Dispatch due scheduled app notifications');

Schedule::command('notifications:dispatch-scheduled')
    ->everyMinute()
    ->withoutOverlapping(10);

Schedule::command('activitylog:clean')
    ->daily();

Schedule::command('model:prune', [
    '--model' => [Device::class],
])->daily();
