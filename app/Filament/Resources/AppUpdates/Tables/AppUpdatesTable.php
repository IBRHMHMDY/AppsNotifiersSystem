<?php

namespace App\Filament\Resources\AppUpdates\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AppUpdatesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('clientApp.name')
                    ->label('التطبيق')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('version')
                    ->label('الإصدار')
                    ->searchable(),
                IconColumn::make('is_force_update')
                    ->label('إجباري')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->label('تاريخ النشر')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
