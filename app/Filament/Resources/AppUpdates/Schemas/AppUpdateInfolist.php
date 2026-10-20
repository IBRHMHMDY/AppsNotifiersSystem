<?php

declare(strict_types=1);

namespace App\Filament\Resources\AppUpdates\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AppUpdateInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('معلومات الإصدار والسياسات')
                    ->schema([
                        TextEntry::make('clientApp.name')
                            ->label('التطبيق'),

                        TextEntry::make('target_os')
                            ->label('نظام التشغيل')
                            ->badge()
                            ->placeholder('جميع الأنظمة'),

                        TextEntry::make('version')
                            ->label('رقم الإصدار')
                            ->badge()
                            ->color('primary'),

                        TextEntry::make('rollout_percentage')
                            ->label('نسبة الإطلاق التدريجي')
                            ->suffix('%')
                            ->badge()
                            ->color('info'),

                        TextEntry::make('min_supported_version')
                            ->label('الحد الأدنى المدعوم')
                            ->placeholder('-'),

                        TextEntry::make('blocked_versions')
                            ->label('الإصدارات المحظورة')
                            ->badge()
                            ->color('danger')
                            ->placeholder('لا يوجد'),

                        IconEntry::make('is_force_update')
                            ->label('تحديث إجباري عام')
                            ->boolean(),

                        IconEntry::make('is_active')
                            ->label('مفعل')
                            ->boolean(),

                        TextEntry::make('store_url')
                            ->label('رابط المتجر')
                            ->copyable()
                            ->columnSpanFull(),

                        TextEntry::make('release_notes')
                            ->label('ملاحظات الإصدار')
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ])
                    ->columns(4),
            ]);
    }
}
