<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\Notifications\DispatchNotificationAction;
use App\Enums\NotificationStatus;
use App\Models\AppNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

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

    /**
     * يتم استدعاء هذه الدالة تلقائياً إذا فشلت المهمة تماماً
     */
    public function failed(Throwable $exception): void
    {
        // إعادة حالة الإشعار إلى "فشل" حتى لا يبقى معلقاً
        $this->notification->update([
            'status' => NotificationStatus::FAILED,
        ]);
        
        // (اختياري) يمكنك هنا تسجيل الخطأ في ملف laravel.log
        \Illuminate\Support\Facades\Log::error('Notification Job Failed: ' . $exception->getMessage());
    }
}