<?php

declare(strict_types=1);

namespace App\Filament\Resources\AppNotifications\Tables;

use App\Actions\Notifications\DispatchNotificationAction;
use App\Enums\NotificationStatus;
use App\Enums\NotificationTargetType;
use App\Enums\NotificationType;
use App\Enums\OsType;
use App\Models\AppNotification;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ReplicateAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class AppNotificationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                SpatieMediaLibraryImageColumn::make('notification_image')
                    ->label('الصورة')
                    ->collection('notification_image')
                    ->conversion('thumb')
                    ->circular()
                    ->defaultImageUrl(url('/favicon.ico'))
                    ->toggleable(),

                TextColumn::make('title')
                    ->label('الإشعار')
                    ->searchable(['title', 'body', 'topic'])
                    ->weight('bold')
                    ->state(fn (AppNotification $record): string => $record->type?->isSilent()
                        ? 'مزامنة صامتة (Silent Payload)'
                        : ($record->title ?: 'بدون عنوان'))
                    ->description(fn (AppNotification $record): string => $record->type?->isSilent()
                        ? 'المفاتيح: ' . implode(', ', array_keys($record->payload_data ?? []))
                        : Str::limit((string) $record->body, 45))
                    ->wrap(),

                TextColumn::make('clientApp.name')
                    ->label('التطبيق')
                    ->placeholder('كل التطبيقات')
                    ->badge()
                    ->color('primary')
                    ->sortable(),

                TextColumn::make('type')
                    ->label('النوع')
                    ->badge()
                    ->sortable(),

                TextColumn::make('target_type')
                    ->label('الاستهداف والجمهور')
                    ->badge()
                    ->description(fn (AppNotification $record): string => $record->target_summary)
                    ->sortable(),

                TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->sortable(),

                TextColumn::make('sent_count')
                    ->label('المرسل / الفاشل')
                    ->numeric()
                    ->sortable()
                    ->color('success')
                    ->description(fn (AppNotification $record): string => $record->failed_count > 0
                        ? "فشل: {$record->failed_count}"
                        : 'بدون أخطاء'),

                TextColumn::make('CTR')
                    ->label('التفاعل (CTR)')
                    ->state(function (AppNotification $record): string {
                        if ($record->sent_count === 0) {
                            return '0%';
                        }
                        $ctr = ($record->opened_count / $record->sent_count) * 100;

                        return round($ctr, 1) . '%';
                    })
                    ->description(fn (AppNotification $record): string => "فتح: {$record->opened_count} • نقر: {$record->clicked_count}")
                    ->badge()
                    ->color(fn (string $state): string => (float) $state >= 10.0 ? 'success' : 'gray'),

                TextColumn::make('scheduled_at')
                    ->label('الجدولة / الإرسال')
                    ->dateTime('Y-m-d h:i A')
                    ->placeholder('إرسال فوري')
                    ->description(fn (AppNotification $record): string => $record->sent_at
                        ? 'أُرسل: ' . $record->sent_at->format('Y-m-d h:i A')
                        : 'لم يُرسل بعد')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('client_app_id')
                    ->label('التطبيق')
                    ->relationship('clientApp', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('type')
                    ->label('نوع الإشعار')
                    ->options(NotificationType::class),

                SelectFilter::make('target_type')
                    ->label('طريقة الاستهداف')
                    ->options(NotificationTargetType::class),

                SelectFilter::make('status')
                    ->label('حالة الإشعار')
                    ->options(NotificationStatus::class),

                SelectFilter::make('target_os')
                    ->label('نظام التشغيل')
                    ->options(OsType::class),
            ])
            ->recordActions([
                Action::make('dispatch')
                    ->label('إرسال الآن')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('تأكيد الإرسال الفوري')
                    ->modalDescription(fn (AppNotification $record): string => $record->scheduled_at !== null && $record->scheduled_at->isFuture()
                        ? 'هذا الإشعار مجدول للإرسال في (' . $record->scheduled_at->format('Y-m-d h:i A') . '). هل تريد تجاوز الجدولة وإرساله الآن فوراً؟'
                        : 'هل أنت متأكد من إرسال هذا الإشعار الآن للجمهور المستهدف (' . $record->target_summary . ')؟'
                    )
                    ->modalSubmitActionLabel('نعم، أرسل الآن')
                    ->visible(fn (AppNotification $record): bool => in_array($record->status, [
                        NotificationStatus::PENDING,
                        NotificationStatus::FAILED,
                    ], true))
                    ->action(function (AppNotification $record, DispatchNotificationAction $dispatcher): void {
                        $dispatcher->execute($record, forceNow: true);

                        Notification::make()
                            ->title('تم دفع الإشعار للإرسال الفوري')
                            ->body('تتم الآن معالجة الإشعار في الخلفية وإرساله للأجهزة المستهدفة.')
                            ->success()
                            ->send();
                    }),

                ReplicateAction::make()
                    ->label('تكرار')
                    ->icon('heroicon-o-document-duplicate')
                    ->color('gray')
                    ->modalHeading('نسخ الإشعار كحملة جديدة')
                    ->modalDescription('سيتم إنشاء نسخة جديدة من هذا الإشعار بحالة (قيد الانتظار) مع تصفير عدادات الإحصائيات.')
                    ->excludeAttributes([
                        'sent_count',
                        'failed_count',
                        'delivered_count',
                        'opened_count',
                        'clicked_count',
                        'sent_at',
                    ])
                    ->beforeReplicaSaved(function (AppNotification $replica): void {
                        $replica->status = NotificationStatus::PENDING;
                        $replica->scheduled_at = now()->addHour();
                        $replica->sent_count = 0;
                        $replica->failed_count = 0;
                        $replica->delivered_count = 0;
                        $replica->opened_count = 0;
                        $replica->clicked_count = 0;
                        $replica->sent_at = null;
                    })
                    ->successNotificationTitle('تم نسخ الإشعار وجدولته بعد ساعة من الآن (يمكنك تعديله قبل الإرسال)'),

                ViewAction::make(),

                EditAction::make()
                    ->visible(fn (AppNotification $record): bool => in_array($record->status, [
                        NotificationStatus::PENDING,
                        NotificationStatus::FAILED,
                    ], true)),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}