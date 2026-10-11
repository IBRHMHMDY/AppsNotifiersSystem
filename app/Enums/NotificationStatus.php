<?php

declare(strict_types=1);

namespace App\Enums;

enum NotificationStatus: string
{
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case SENT = 'sent';
    case FAILED = 'failed';

    public function label(): string
    {
        return match($this) {
            self::PENDING => 'قيد الانتظار',
            self::PROCESSING => 'جاري الإرسال',
            self::SENT => 'تم الإرسال',
            self::FAILED => 'فشل الإرسال',
        };
    }
}