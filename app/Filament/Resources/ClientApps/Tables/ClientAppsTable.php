<?php

namespace App\Filament\Resources\ClientApps\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ClientAppsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('الاسم')
                    ->searchable(),
                TextColumn::make('bundle_id')
                    ->label('Bundle ID')
                    ->searchable(),
                TextColumn::make('app_key')
                    ->label('App Key')
                    ->copyable()
                    ->copyMessage('تم نسخ المفتاح!')
                    ->icon('heroicon-o-clipboard-document'),
                IconColumn::make('is_active')
                    ->label('الحالة')
                    ->boolean(),
                TextColumn::make('devices_count')
                    ->label('عدد الأجهزة')
                    ->counts('devices'),
                TextColumn::make('created_at')
                    ->label('تاريخ الإضافة')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_active')->label('حالة التفعيل'),
            ])
            ->recordActions([
                EditAction::make(),
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
