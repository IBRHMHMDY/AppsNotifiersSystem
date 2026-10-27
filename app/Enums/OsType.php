<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum OsType: string implements HasColor, HasIcon, HasLabel
{
    case ANDROID = 'android';
    case IOS = 'ios';

    /**
     * إرجاع النص المقروء لعرضه في واجهات Filament والمودلز.
     */
    public function getLabel(): ?string
    {
        return match ($this) {
            self::ANDROID => 'Android',
            self::IOS => 'iOS',
        };
    }

    /**
     * تحديد اللون المناسب عند عرض الـ Enum كـ Badge في Filament.
     */
    public function getColor(): string|array|null
    {
        return match ($this) {
            self::ANDROID => 'success',
            self::IOS => 'info',
        };
    }

    /**
     * تحديد الأيقونة المناسبة للعرض في الجداول أو الفورم.
     */
    public function getIcon(): ?string
    {
        return match ($this) {
            self::ANDROID => 'heroicon-o-device-phone-mobile',
            self::IOS => 'heroicon-o-device-phone-mobile',
        };
    }
}
