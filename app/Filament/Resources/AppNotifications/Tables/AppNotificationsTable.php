<?php

declare(strict_types=1);

namespace App\Filament\Resources\AppNotifications\Tables;

use App\Actions\Notifications\DispatchNotificationAction;
use App\Enums\NotificationStatus;
use App\Enums\OsType;
use App\Models\AppNotification;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AppNotificationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                SpatieMediaLibraryImageColumn::make('notification_image')
                    ->label('الصورة')
                    ->collection('notification_image')
                    ->circular(),

                TextColumn::make('title')
                    ->label('عنوان الإشعار')
                    ->searchable()
                    ->limit(30)
                    ->weight('bold'),

                TextColumn::make('clientApp.name')
                    ->label('التطبيق')
                    ->badge()
                    ->color('primary')
                    ->sortable(),

                TextColumn::make('targeting')
                    ->label('الاستهداف')
                    ->badge()
                    ->state(function (AppNotification $record): string {
                        if (! empty($record->topic)) {
                            return 'topic';
                        }

                        if ($record->target_os !== null) {
                            return 'os';
                        }

                        return 'all';
                    })
                    ->formatStateUsing(fn (string $state, AppNotification $record): string => match ($state) {
                        'all' => 'الكل (Broadcast)',
                        'os' => 'نظام: ' . ($record->target_os?->getLabel() ?? ''),
                        'topic' => 'موضوع: ' . ($record->topic ?? ''),
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'all' => 'info',
                        'os' => 'warning',
                        'topic' => 'success',
                        default => 'gray',
                    }),

                TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->sortable(),

                TextColumn::make('sent_count')
                    ->label('تم الإرسال')
                    ->numeric()
                    ->sortable()
                    ->color('success'),

                TextColumn::make('opened_count')
                    ->label('تم الفتح')
                    ->numeric()
                    ->sortable()
                    ->color('info'),

                TextColumn::make('CTR')
                    ->label('نسبة التفاعل (CTR)')
                    ->state(function (AppNotification $record): string {
                        if ($record->sent_count === 0) {
                            return '0%';
                        }
                        $ctr = ($record->opened_count / $record->sent_count) * 100;

                        return round($ctr, 1) . '%';
                    })
                    ->badge()
                    ->color(fn (string $state): string => (float) $state > 10.0 ? 'success' : 'gray'),

                TextColumn::make('scheduled_at')
                    ->label('وقت الجدولة')
                    ->dateTime('Y-m-d h:i A')
                    ->placeholder('إرسال فوري')
                    ->sortable(),

                TextColumn::make('sent_at')
                    ->label('تاريخ الإرسال الفعلي')
                    ->dateTime('Y-m-d h:i A')
                    ->placeholder('لم يُرسل بعد')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('client_app_id')
                    ->label('التطبيق')
                    ->relationship('clientApp', 'name'),

                SelectFilter::make('status')
                    ->label('حالة الإشعار')
                    ->options(NotificationStatus::class),

                SelectFilter::make('target_os')
                    ->label('نظام التشغيل')
                    ->options(OsType::class),
            ])
            ->recordActions([
                Action::make('dispatch')
                    ->label('Send Now')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('تأكيد الإرسال الفوري')
                    ->modalDescription(fn (AppNotification $record): string => $record->scheduled_at !== null && $record->scheduled_at->isFuture()
                        ? 'هذا الإشعار مجدول للإرسال في (' . $record->scheduled_at->format('Y-m-d h:i A') . '). هل تريد تجاوز الجدولة وإرساله الآن فوراً؟'
                        : 'هل أنت متأكد من إرسال هذا الإشعار الآن لجميع الأجهزة المستهدفة؟'
                    )
                    ->modalSubmitActionLabel('نعم، أرسل الآن')
                    ->visible(fn (AppNotification $record): bool => in_array($record->status, [
                        NotificationStatus::PENDING,
                        NotificationStatus::FAILED,
                    ], true))
                    ->action(function (AppNotification $record, DispatchNotificationAction $dispatcher): void {
                        // تغيير اسم المتغير إلى $dispatcher لتجنب التعارض مع متغير $action المحجوز في Filament
                        $dispatcher->execute($record, forceNow: true);

                        Notification::make()
                            ->title('تم دفع الإشعار للإرسال الفوري')
                            ->body('تتم الآن معالجة الإشعار في الخلفية وإرساله للأجهزة.')
                            ->success()
                            ->send();
                    }),

                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}