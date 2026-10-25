<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum NotificationType: string implements HasColor, HasDescription, HasIcon, HasLabel
{
    case General = 'general';
    case Promotional = 'promotional';
    case Transactional = 'transactional';
    case SystemAlert = 'system_alert';
    case SilentData = 'silent_data';

    public function getLabel(): string
    {
        return match ($this) {
            self::General => 'إشعار عام',
            self::Promotional => 'ترويجي / تسويقي',
            self::Transactional => 'إجرائي / عمليات',
            self::SystemAlert => 'تنبيه نظام / تحديث',
            self::SilentData => 'بيانات صامتة (خلفية)',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::General => 'إشعار قياسي للأخبار أو التنبيهات الاعتيادية للمستخدمين.',
            self::Promotional => 'عروض تسويقية، خصومات، أو حملات ترويجية.',
            self::Transactional => 'مرتبط بحدث أو عملية محددة قام بها المستخدم داخل التطبيق.',
            self::SystemAlert => 'تنبيهات إدارية هامة، صيانة، أو إشعارات توفر تحديث جديد.',
            self::SilentData => 'يُرسل في الخلفية بدون صوت أو نافذة منبثقة لمزامنة بيانات التطبيق.',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::General => 'primary',
            self::Promotional => 'success',
            self::Transactional => 'info',
            self::SystemAlert => 'danger',
            self::SilentData => 'gray',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::General => 'heroicon-o-bell',
            self::Promotional => 'heroicon-o-megaphone',
            self::Transactional => 'heroicon-o-bolt',
            self::SystemAlert => 'heroicon-o-shield-exclamation',
            self::SilentData => 'heroicon-o-code-bracket-square',
        };
    }

    /**
     * يحدد ما إذا كان الإشعار صامتاً (يرسل Data Payload فقط بدون Notification Block).
     */
    public function isSilent(): bool
    {
        return $this === self::SilentData;
    }
}