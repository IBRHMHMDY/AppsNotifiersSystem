<?php

namespace App\Filament\Resources\AppNotifications\Tables;

use App\Enums\NotificationStatus;
use App\Jobs\ProcessAppNotificationJob;
use App\Models\AppNotification;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
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
                SpatieMediaLibraryImageColumn::make('image')
                    ->label('الصورة')
                    ->collection('notification_image')
                    ->circular(),

                TextColumn::make('title')
                    ->label('العنوان')
                    ->searchable()
                    ->limit(30),

                TextColumn::make('clientApp.name')
                    ->label('التطبيق')
                    ->default('الجميع'),

                TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->color(fn (NotificationStatus $state): string => match ($state) {
                        NotificationStatus::PENDING => 'gray',
                        NotificationStatus::PROCESSING => 'warning',
                        NotificationStatus::SENT => 'success',
                        NotificationStatus::FAILED => 'danger',
                    }),

                TextColumn::make('sent_at')
                    ->label('تاريخ الإرسال')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('الحالة')
                    ->options(NotificationStatus::class),
            ])
            ->recordActions([
                // زر إرسال الإشعار الآن
                Action::make('dispatch')
                    ->label('إرسال الآن')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('تأكيد الإرسال')
                    ->modalDescription('هل أنت متأكد من إرسال هذا الإشعار الآن لجميع الأجهزة المستهدفة؟')
                    // لا يظهر الزر إلا إذا كانت الحالة "قيد الانتظار"
                    ->visible(fn (AppNotification $record) => $record->status === NotificationStatus::PENDING)
                    // حقن (Inject) الـ Action الخاص بنا وتنفيذه
                    ->action(function (AppNotification $record) {
                        try {
                            // تحويل حالة الإشعار فوراً إلى "جاري الإرسال" لمنع الضغط عليه مرة أخرى
                            $record->update(['status' => NotificationStatus::PROCESSING]);

                            // إرسال المهمة إلى طابور العمل
                            ProcessAppNotificationJob::dispatch($record);

                            Notification::make()
                                ->title('تمت الإضافة للطابور')
                                ->body('سيتم إرسال الإشعار في الخلفية قريباً.')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('حدث خطأ')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                EditAction::make()
            ])
            ->defaultSort('created_at', 'desc');
    }
}
