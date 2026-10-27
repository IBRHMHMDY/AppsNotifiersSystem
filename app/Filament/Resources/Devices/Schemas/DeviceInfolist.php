<?php

// app/Filament/Resources/Devices/Schemas/DeviceInfolist.php

declare(strict_types=1);

namespace App\Filament\Resources\Devices\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class DeviceInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('بيانات الجهاز والتطبيق')
                    ->icon('heroicon-o-cpu-chip')
                    ->schema([
                        TextEntry::make('id')
                            ->label('معرّف السجل (#ID)')
                            ->badge()
                            ->color('gray'),

                        TextEntry::make('clientApp.name')
                            ->label('التطبيق المرتبط')
                            ->badge()
                            ->color('primary'),

                        TextEntry::make('os_type')
                            ->label('نظام التشغيل')
                            ->badge(),

                        TextEntry::make('app_version')
                            ->label('إصدار التطبيق المثبت')
                            ->badge()
                            ->color('info'),

                        TextEntry::make('subscribed_topics')
                            ->label('المواضيع المشترك بها (FCM Topics)')
                            ->badge()
                            ->color('warning')
                            ->formatStateUsing(fn (?string $state): string => $state ? "#{$state}" : '-')
                            ->placeholder('غير مشترك في أي موضوع')
                            ->columnSpanFull(),

                        TextEntry::make('fcm_token')
                            ->label('معرّف الإشعارات (FCM Token)')
                            ->copyable()
                            ->copyMessage('تم نسخ الـ FCM Token بنجاح')
                            ->fontFamily('mono')
                            ->columnSpanFull(),

                        TextEntry::make('last_active_at')
                            ->label('آخر نشاط مسجل')
                            ->dateTime('Y-m-d h:i:s A')
                            ->since()
                            ->placeholder('-'),

                        TextEntry::make('created_at')
                            ->label('تاريخ أول تسجيل')
                            ->dateTime('Y-m-d h:i:s A')
                            ->placeholder('-'),
                    ])->columns(4),
            ]);
    }
}
