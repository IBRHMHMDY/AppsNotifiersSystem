<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\Notifications\DispatchNotificationAction;
use App\Models\AppNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessAppNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * تحديد أقصى وقت لتنفيذ المهمة (بالثواني)
     */
    public $timeout = 300; 

    public function __construct(
        public readonly AppNotification $notification
    ) {}

    /**
     * تنفيذ المهمة.
     * (Laravel سيقوم بحقن DispatchNotificationAction تلقائياً)
     */
    public function handle(DispatchNotificationAction $action): void
    {
        $action->execute($this->notification);
    }
}