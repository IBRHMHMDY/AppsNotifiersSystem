<?php

// app/Filament/Resources/ClientApps/Tables/ClientAppsTable.php

declare(strict_types=1);

namespace App\Filament\Resources\ClientApps\Tables;

use App\Models\ClientApp;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class ClientAppsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('اسم التطبيق')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('bundle_id')
                    ->label('Bundle ID')
                    ->searchable()
                    ->copyable()
                    ->fontFamily('mono'),

                TextColumn::make('firebase_project_id')
                    ->label('مشروع Firebase')
                    ->placeholder('الافتراضي (Default)')
                    ->badge()
                    ->color(fn (?string $state): string => $state ? 'warning' : 'gray')
                    ->icon('heroicon-o-fire'),

                TextColumn::make('app_key')
                    ->label('App Key')
                    ->copyable()
                    ->copyMessage('تم نسخ مفتاح التطبيق بنجاح')
                    ->icon('heroicon-o-clipboard-document')
                    ->fontFamily('mono')
                    ->limit(18)
                    ->tooltip('اضغط لنسخ المفتاح الكامل'),

                IconColumn::make('is_active')
                    ->label('الحالة')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('devices_count')
                    ->label('الأجهزة')
                    ->counts('devices')
                    ->badge()
                    ->color('success')
                    ->sortable(),

                TextColumn::make('updates_count')
                    ->label('التحديثات')
                    ->counts('updates')
                    ->badge()
                    ->color('info')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('حالة التفعيل'),
                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),

                Action::make('regenerate_key')
                    ->label('New Key')
                    ->icon('heroicon-o-key')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('إعادة توليد مفتاح التطبيق (App Key)')
                    ->modalDescription('تحذير: سيؤدي هذا الإجراء إلى إبطال المفتاح الحالي فوراً، ولن تتمكن تطبيقات الموبايل التي تستخدم المفتاح القديم من الاتصال بالـ API حتى يتم تحديثها.')
                    ->action(function (ClientApp $record): void {
                        $record->update([
                            'app_key' => Str::random(40),
                        ]);

                        Notification::make()
                            ->title('تم توليد مفتاح تطبيق جديد بنجاح')
                            ->success()
                            ->send();
                    }),

                DeleteAction::make(),
                RestoreAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('activate_selected')
                        ->label('تفعيل المحدد')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(fn (Collection $records) => $records->each->update(['is_active' => true]))
                        ->deselectRecordsAfterCompletion(),

                    BulkAction::make('deactivate_selected')
                        ->label('إيقاف المحدد')
                        ->icon('heroicon-o-x-circle')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->action(fn (Collection $records) => $records->each->update(['is_active' => false]))
                        ->deselectRecordsAfterCompletion(),

                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
