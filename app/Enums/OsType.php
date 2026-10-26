<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum OsType: string implements HasLabel, HasColor, HasIcon
{
    case Android = 'android';
    case Ios = 'ios';

    /**
     * إرجاع النص المقروء لعرضه في واجهات Filament والمودلز.
     */
    public function getLabel(): ?string
    {
        return match ($this) {
            self::Android => 'Android',
            self::Ios => 'iOS',
        };
    }

    /**
     * تحديد اللون المناسب عند عرض الـ Enum كـ Badge في Filament.
     */
    public function getColor(): string | array | null
    {
        return match ($this) {
            self::Android => 'success',
            self::Ios => 'info',
        };
    }

    /**
     * تحديد الأيقونة المناسبة للعرض في الجداول أو الفورم.
     */
    public function getIcon(): ?string
    {
        return match ($this) {
            self::Android => 'heroicon-o-device-phone-mobile',
            self::Ios => 'heroicon-o-device-phone-mobile',
        };
    }
}