<?php

// app/Filament/Resources/ClientApps/Schemas/ClientAppInfolist.php

declare(strict_types=1);

namespace App\Filament\Resources\ClientApps\Schemas;

use App\Models\ClientApp;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ClientAppInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('تفاصيل التطبيق والربط البرمجي')
                    ->schema([
                        TextEntry::make('name')
                            ->label('اسم التطبيق')
                            ->weight('bold'),

                        TextEntry::make('bundle_id')
                            ->label('Bundle ID')
                            ->copyable()
                            ->fontFamily('mono'),

                        TextEntry::make('app_key')
                            ->label('مفتاح الربط (App Key)')
                            ->copyable()
                            ->fontFamily('mono'),

                        TextEntry::make('firebase_project_id')
                            ->label('مشروع Firebase المرتبط')
                            ->placeholder('المشروع الافتراضي للسيرفر (Default Config)')
                            ->badge()
                            ->color(fn (?string $state): string => $state ? 'warning' : 'gray'),

                        IconEntry::make('is_active')
                            ->label('حالة التفعيل')
                            ->boolean(),

                        IconEntry::make('is_under_maintenance')
                            ->label('وضع الصيانة')
                            ->boolean(),

                        TextEntry::make('maintenance_message')
                            ->label('رسالة الصيانة')
                            ->placeholder('لا توجد رسالة صيانة')
                            ->visible(fn (ClientApp $record): bool => (bool) $record->is_under_maintenance)
                            ->columnSpanFull(),

                        TextEntry::make('created_at')
                            ->label('تاريخ الإنشاء')
                            ->dateTime()
                            ->placeholder('-'),

                        TextEntry::make('updated_at')
                            ->label('آخر تحديث')
                            ->dateTime()
                            ->placeholder('-'),

                        TextEntry::make('deleted_at')
                            ->label('تاريخ الحذف')
                            ->dateTime()
                            ->visible(fn (ClientApp $record): bool => $record->trashed()),
                    ])->columns(2),
            ]);
    }
}
