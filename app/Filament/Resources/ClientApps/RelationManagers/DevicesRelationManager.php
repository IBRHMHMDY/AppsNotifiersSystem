<?php

// app/Filament/Resources/ClientApps/RelationManagers/DevicesRelationManager.php

declare(strict_types=1);

namespace App\Filament\Resources\ClientApps\RelationManagers;

use App\Enums\OsType;
use App\Filament\Resources\Devices\Tables\DevicesTable;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class DevicesRelationManager extends RelationManager
{
    protected static string $relationship = 'devices';

    protected static ?string $title = 'الأجهزة المسجلة (Devices)';

    protected static ?string $modelLabel = 'جهاز';

    protected static ?string $pluralModelLabel = 'الأجهزة';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('fcm_token')
            ->columns([
                TextColumn::make('id')
                    ->label('#')
                    ->sortable(),

                TextColumn::make('os_type')
                    ->label('نظام التشغيل')
                    ->badge()
                    ->sortable(),

                TextColumn::make('app_version')
                    ->label('إصدار التطبيق')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('info'),

                TextColumn::make('subscribed_topics')
                    ->label('المواضيع (Topics)')
                    ->badge()
                    ->color('warning')
                    ->formatStateUsing(fn (?string $state): string => $state ? "#{$state}" : '-')
                    ->placeholder('لا يوجد'),

                TextColumn::make('fcm_token')
                    ->label('FCM Token')
                    ->limit(28)
                    ->copyable()
                    ->copyMessage('تم نسخ FCM Token')
                    ->fontFamily('mono')
                    ->searchable(),

                TextColumn::make('last_active_at')
                    ->label('آخر ظهور')
                    ->dateTime()
                    ->since()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('تاريخ التسجيل')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('os_type')
                    ->label('نظام التشغيل')
                    ->options(OsType::class),
            ])
            ->recordActions([
                DevicesTable::makeSendTestPushAction(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('last_active_at', 'desc');
    }
}
