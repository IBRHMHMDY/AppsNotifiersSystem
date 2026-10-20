<?php

declare(strict_types=1);

namespace App\Enums;

enum OsType: string
{
    case ANDROID = 'android';
    case IOS = 'ios';

    public function label(): string
    {
        return match ($this) {
            self::ANDROID => 'Android',
            self::IOS => 'iOS',
        };
    }
}
