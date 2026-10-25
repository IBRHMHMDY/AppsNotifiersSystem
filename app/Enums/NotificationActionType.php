<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum NotificationActionType: string implements HasColor, HasDescription, HasIcon, HasLabel
{
    case OpenApp = 'open_app';
    case DeepLink = 'deep_link';
    case ExternalUrl = 'external_url';

    public function getLabel(): string
    {
        return match ($this) {
            self::OpenApp => 'فتح التطبيق فقط',
            self::DeepLink => 'توجيه داخلي (Deep Link / Screen)',
            self::ExternalUrl => 'فتح رابط خارجي (URL)',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::OpenApp => 'يفتح الشاشة الرئيسية للتطبيق بدون توجيه إضافي.',
            self::DeepLink => 'يوجه المستخدم إلى شاشة أو عنصر محدد داخل التطبيق (مثل: product/15).',
            self::ExternalUrl => 'يفتح رابط ويب في المتصفح (مثل: https://example.com).',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::OpenApp => 'gray',
            self::DeepLink => 'primary',
            self::ExternalUrl => 'info',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::OpenApp => 'heroicon-o-arrow-top-right-on-square',
            self::DeepLink => 'heroicon-o-arrows-Pointing-in',
            self::ExternalUrl => 'heroicon-o-link',
        };
    }

    /**
     * يحدد ما إذا كان هذا الإجراء يتطلب إدخال قيمة (رابط أو مسار شاشة).
     */
    public function requiresValue(): bool
    {
        return in_array($this, [self::DeepLink, self::ExternalUrl], true);
    }
}