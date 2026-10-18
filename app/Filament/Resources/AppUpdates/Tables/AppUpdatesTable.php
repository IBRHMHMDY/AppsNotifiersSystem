<?php

declare(strict_types=1);

namespace App\Filament\Resources\AppUpdates\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AppUpdatesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('clientApp'))
            ->columns([
                TextColumn::make('clientApp.name')
                    ->label('التطبيق')
                    ->sortable()
                    ->searchable()
                    ->weight('bold'),

                TextColumn::make('version')
                    ->label('رقم الإصدار')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('primary'),

                IconColumn::make('is_force_update')
                    ->label('تحديث إجباري')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('store_url')
                    ->label('رابط المتجر')
                    ->limit(30)
                    ->copyable()
                    ->copyMessage('تم نسخ رابط المتجر')
                    ->icon('heroicon-o-link')
                    ->placeholder('-'),

                TextColumn::make('release_notes')
                    ->label('ملاحظات الإصدار')
                    ->limit(40)
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->placeholder('-'),

                TextColumn::make('created_at')
                    ->label('تاريخ النشر')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('client_app_id')
                    ->label('التطبيق')
                    ->relationship('clientApp', 'name')
                    ->searchable()
                    ->preload(),

                TernaryFilter::make('is_force_update')
                    ->label('تحديث إجباري'),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}