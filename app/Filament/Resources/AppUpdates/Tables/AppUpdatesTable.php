<?php

declare(strict_types=1);

namespace App\Filament\Resources\AppUpdates\Tables;

use App\Enums\OsType;
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

                TextColumn::make('target_os')
                    ->label('النظام')
                    ->badge()
                    ->placeholder('الكل'),

                TextColumn::make('version')
                    ->label('الإصدار')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('primary'),

                TextColumn::make('rollout_percentage')
                    ->label('نسبة الإطلاق')
                    ->suffix('%')
                    ->badge()
                    ->color(fn (int $state): string => $state < 100 ? 'warning' : 'success')
                    ->sortable(),

                TextColumn::make('min_supported_version')
                    ->label('أدنى إصدار مدعوم')
                    ->placeholder('-')
                    ->badge()
                    ->color('gray'),

                TextColumn::make('blocked_versions')
                    ->label('الإصدارات المحظورة')
                    ->badge()
                    ->color('danger')
                    ->placeholder('لا يوجد'),

                IconColumn::make('is_force_update')
                    ->label('إجبار عام')
                    ->boolean()
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('مفعل')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('تاريخ النشر')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('client_app_id')
                    ->label('التطبيق')
                    ->relationship('clientApp', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('target_os')
                    ->label('نظام التشغيل')
                    ->options(OsType::class),

                TernaryFilter::make('is_force_update')
                    ->label('تحديث إجباري عام'),

                TernaryFilter::make('is_active')
                    ->label('حالة التفعيل'),
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
