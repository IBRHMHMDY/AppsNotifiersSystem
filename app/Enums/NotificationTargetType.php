<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum NotificationTargetType: string implements HasColor, HasDescription, HasIcon, HasLabel
{
    case AllDevices = 'all_devices';
    case Topic = 'topic';
    case Segmented = 'segmented';
    case SpecificDevices = 'specific_devices';

    public function getLabel(): string
    {
        return match ($this) {
            self::AllDevices => 'كل أجهزة التطبيق (Broadcast)',
            self::Topic => 'موضوع مشترك (FCM Topic)',
            self::Segmented => 'شريحة مخصصة (Segmented)',
            self::SpecificDevices => 'أجهزة محددة (Specific)',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::AllDevices => 'إرسال شامل لجميع الأجهزة النشطة المسجلة ضمن هذا التطبيق.',
            self::Topic => 'إرسال فوري للأجهزة المشتركة في قناة أو موضوع محدد (مثل: news, offers).',
            self::Segmented => 'فلترة دقيقة للأجهزة حسب نظام التشغيل (Android/iOS) أو إصدار التطبيق.',
            self::SpecificDevices => 'اختيار أجهزة بعينها بالاسم أو المعرّف (مفيد للاختبار أو الدعم الفني).',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::AllDevices => 'primary',
            self::Topic => 'warning',
            self::Segmented => 'info',
            self::SpecificDevices => 'success',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::AllDevices => 'heroicon-o-globe-alt',
            self::Topic => 'heroicon-o-hashtag',
            self::Segmented => 'heroicon-o-funnel',
            self::SpecificDevices => 'heroicon-o-device-phone-mobile',
        };
    }
}