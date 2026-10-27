<?php

// app/Filament/RelationManagers/ActivitiesRelationManager.php

declare(strict_types=1);

namespace App\Filament\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Activitylog\Models\Activity;

class ActivitiesRelationManager extends RelationManager
{
    protected static string $relationship = 'activities';

    protected static ?string $title = 'سجل النشاطات والتعديلات (Audit Log)';

    protected static ?string $modelLabel = 'نشاط';

    protected static ?string $pluralModelLabel = 'سجل النشاطات';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('causer')->latest())
            ->columns([
                TextColumn::make('event')
                    ->label('الحدث')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'created' => 'إنشاء',
                        'updated' => 'تعديل',
                        'deleted' => 'حذف',
                        'restored' => 'استعادة',
                        default => (string) $state,
                    })
                    ->color(fn (?string $state): string => match ($state) {
                        'created' => 'success',
                        'updated' => 'info',
                        'deleted' => 'danger',
                        'restored' => 'warning',
                        default => 'gray',
                    }),

                TextColumn::make('causer.name')
                    ->label('بواسطة المستخدم')
                    ->placeholder('النظام / API تلقائي')
                    ->icon('heroicon-o-user'),

                TextColumn::make('changes_summary')
                    ->label('الحقول المعدّلة')
                    ->state(function (Activity $record): string {
                        $changes = $record->changes();
                        $attributes = $changes->get('attributes', []);

                        if (! is_array($attributes) || $attributes === []) {
                            return '-';
                        }

                        return implode(', ', array_keys($attributes));
                    })
                    ->wrap(),

                TextColumn::make('created_at')
                    ->label('التوقيت')
                    ->dateTime('Y-m-d h:i:s A')
                    ->since()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
