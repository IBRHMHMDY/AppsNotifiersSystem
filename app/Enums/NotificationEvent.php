<?php

declare(strict_types=1);

namespace App\Enums;

enum NotificationEvent: string
{
    case DELIVERED = 'delivered';
    case OPENED = 'opened';
    case CLICKED = 'clicked';

    /**
     * تحديد العمود المقابل في جدول app_notifications لتحديثه ذرياً.
     */
    public function counterColumn(): string
    {
        return match ($this) {
            self::DELIVERED => 'delivered_count',
            self::OPENED => 'opened_count',
            self::CLICKED => 'clicked_count',
        };
    }
}
