<?php

declare(strict_types=1);

namespace App\Filament\Resources\AppNotifications\Tables;

use App\Actions\Notifications\DispatchNotificationAction;
use App\Enums\NotificationStatus;
use App\Enums\OsType;
use App\Models\AppNotification;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Throwable;

class AppNotificationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['clientApp', 'media']))
            ->columns([
                SpatieMediaLibraryImageColumn::make('image')
                    ->label('الصورة')
                    ->collection('notification_image')
                    ->circular(),

                TextColumn::make('title')
                    ->label('العنوان')
                    ->searchable()
                    ->weight('bold')
                    ->limit(30),

                TextColumn::make('clientApp.name')
                    ->label('التطبيق')
                    ->default('جميع التطبيقات')
                    ->sortable(),

                TextColumn::make('topic')
                    ->label('الموضوع (Topic)')
                    ->badge()
                    ->color('info')
                    ->placeholder('بث مباشر للأجهزة'),

                TextColumn::make('target_os')
                    ->label('النظام')
                    ->badge()
                    ->placeholder('الكل'),

                TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->color(fn (NotificationStatus $state): string => match ($state) {
                        NotificationStatus::PENDING => 'gray',
                        NotificationStatus::PROCESSING => 'warning',
                        NotificationStatus::SENT => 'success',
                        NotificationStatus::FAILED => 'danger',
                    }),

                TextColumn::make('sent_count')
                    ->label('نجاح / فشل')
                    ->formatStateUsing(fn (AppNotification $record): string => "{$record->sent_count} / {$record->failed_count}")
                    ->badge()
                    ->color(fn (AppNotification $record): string => $record->failed_count > 0 ? 'warning' : 'success'),

                TextColumn::make('opened_count')
                    ->label('مرات الفتح')
                    ->badge()
                    ->color('primary')
                    ->sortable(),

                TextColumn::make('sent_at')
                    ->label('وقت الإرسال')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('-'),
            ])
            ->filters([
                SelectFilter::make('client_app_id')
                    ->label('التطبيق')
                    ->relationship('clientApp', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('status')
                    ->label('الحالة')
                    ->options(NotificationStatus::class),

                SelectFilter::make('target_os')
                    ->label('نظام التشغيل')
                    ->options(OsType::class),
            ])
            ->recordActions([
                Action::make('dispatch')
                    ->label(fn (AppNotification $record): string => $record->status === NotificationStatus::FAILED ? 'إعادة الإرسال' : 'إرسال الآن')
                    ->icon(fn (AppNotification $record): string => $record->status === NotificationStatus::FAILED ? 'heroicon-o-arrow-path' : 'heroicon-o-paper-airplane')
                    ->color(fn (AppNotification $record): string => $record->status === NotificationStatus::FAILED ? 'warning' : 'success')
                    ->requiresConfirmation()
                    ->modalHeading('تأكيد دفع الإشعار للإرسال')
                    ->modalDescription('هل أنت متأكد من جدولة هذا الإشعار في طابور الإرسال الآن؟')
                    ->visible(fn (AppNotification $record): bool => in_array(
                        $record->status,
                        [NotificationStatus::PENDING, NotificationStatus::FAILED],
                        true
                    ))
                    ->action(function (AppNotification $record, DispatchNotificationAction $dispatchAction): void {
                        try {
                            $dispatchAction->execute($record);

                            Notification::make()
                                ->title('تم وضع الإشعار في طابور الإرسال بنجاح')
                                ->success()
                                ->send();
                        } catch (Throwable $e) {
                            Notification::make()
                                ->title('حدث خطأ أثناء جدولة الإشعار')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('dispatch_selected')
                        ->label('إرسال الإشعارات المحددة')
                        ->icon('heroicon-o-paper-airplane')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function (Collection $records, DispatchNotificationAction $dispatchAction): void {
                            $dispatchedCount = 0;

                            /** @var AppNotification $record */
                            foreach ($records as $record) {
                                if (in_array($record->status, [NotificationStatus::PENDING, NotificationStatus::FAILED], true)) {
                                    $dispatchAction->execute($record);
                                    $dispatchedCount++;
                                }
                            }

                            Notification::make()
                                ->title("تم دفع {$dispatchedCount} إشعار إلى طابور الإرسال")
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),

                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
