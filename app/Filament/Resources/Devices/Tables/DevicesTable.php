<?php

// app/Filament/Resources/Devices/Tables/DevicesTable.php

declare(strict_types=1);

namespace App\Filament\Resources\Devices\Tables;

use App\Contracts\PushNotificationServiceInterface;
use App\Enums\OsType;
use App\Models\Device;
use App\Services\TargetAudienceService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DevicesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->deferLoading()
            ->columns([
                TextColumn::make('id')
                    ->label('#')
                    ->sortable(),

                TextColumn::make('clientApp.name')
                    ->label('التطبيق')
                    ->badge()
                    ->color('primary')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('os_type')
                    ->label('النظام')
                    ->badge()
                    ->sortable(),

                TextColumn::make('app_version')
                    ->label('الإصدار')
                    ->badge()
                    ->color('info')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('subscribed_topics')
                    ->label('المواضيع (Topics)')
                    ->badge()
                    ->color('warning')
                    ->formatStateUsing(fn (?string $state): string => $state ? "#{$state}" : '-')
                    ->placeholder('لا يوجد')
                    ->toggleable(),

                TextColumn::make('fcm_token')
                    ->label('FCM Token')
                    ->limit(24)
                    ->copyable()
                    ->copyMessage('تم نسخ FCM Token بنجاح')
                    ->icon('heroicon-o-clipboard-document')
                    ->fontFamily('mono')
                    ->searchable(),

                TextColumn::make('last_active_at')
                    ->label('آخر نشاط')
                    ->since()
                    ->dateTimeTooltip('Y-m-d h:i A')
                    ->sortable()
                    ->color(fn (Device $record): string => $record->last_active_at && $record->last_active_at->gt(now()->subDays(7))
                        ? 'success'
                        : 'gray'),

                TextColumn::make('created_at')
                    ->label('تاريخ التسجيل')
                    ->dateTime('Y-m-d')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('client_app_id')
                    ->label('التطبيق')
                    ->relationship('clientApp', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('os_type')
                    ->label('نظام التشغيل')
                    ->options(OsType::class),

                SelectFilter::make('app_version')
                    ->label('إصدار التطبيق')
                    ->options(fn (): array => app(TargetAudienceService::class)->getAvailableAppVersions()),

                Filter::make('inactive_over_30_days')
                    ->label('أجهزة خاملة منذ +30 يوماً')
                    ->query(fn (Builder $query): Builder => $query->where('last_active_at', '<', now()->subDays(30))),
            ])
            ->recordActions([
                self::makeSendTestPushAction(),
                ViewAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('last_active_at', 'desc');
    }

    /**
     * إجراء موحد لإرسال إشعار تجريبي مباشر لجهاز محدد (يُستخدم في الجدول الرئيسي والـ RelationManager).
     */
    public static function makeSendTestPushAction(): Action
    {
        return Action::make('send_test_push')
            ->label('إشعار تجريبي')
            ->icon('heroicon-o-paper-airplane')
            ->color('info')
            ->modalHeading(fn (Device $record): string => "إرسال إشعار تجريبي للجهاز #{$record->id}")
            ->modalDescription('سيتم إرسال هذا الإشعار مباشرة ولحظياً إلى هذا الجهاز عبر Firebase لاختبار الاتصال.')
            ->modalSubmitActionLabel('إرسال الآن')
            ->visible(fn (Device $record): bool => ! empty($record->fcm_token))
            ->schema([
                TextInput::make('title')
                    ->label('عنوان الإشعار التجريبي')
                    ->default('إشعار تجريبي من المنصة 🔔')
                    ->required()
                    ->maxLength(150),

                Textarea::make('body')
                    ->label('نص الرسالة')
                    ->default('تم استلام الإشعار التجريبي بنجاح على جهازك.')
                    ->required()
                    ->rows(3),

                KeyValue::make('data')
                    ->label('بيانات إضافية (Data Payload - اختياري)')
                    ->keyLabel('المفتاح')
                    ->valueLabel('القيمة')
                    ->default([
                        'test_mode' => 'true',
                        'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                    ]),
            ])
            ->action(function (Device $record, array $data, PushNotificationServiceInterface $pushService): void {
                $record->loadMissing('clientApp');

                $result = $pushService->sendToToken(
                    token: (string) $record->fcm_token,
                    title: (string) $data['title'],
                    body: (string) $data['body'],
                    data: is_array($data['data'] ?? null) ? $data['data'] : [],
                    imageUrl: null,
                    clientApp: $record->clientApp,
                );

                if ($result->isCompletelySuccessful()) {
                    Notification::make()
                        ->title('تم إرسال الإشعار التجريبي بنجاح')
                        ->body('وصل الإشعار إلى خوادم Firebase بنجاح لهذا الجهاز.')
                        ->success()
                        ->send();

                    return;
                }

                // إذا كان التوكن منتهي الصلاحية أو غير مسجل في Firebase، نحذفه تلقائياً
                if ($result->invalidTokens !== []) {
                    $record->delete();

                    Notification::make()
                        ->title('تم حذف الجهاز لعدم صلاحية التوكن (Invalid/Unregistered Token)')
                        ->body('أفاد Firebase بأن هذا التوكن لم يعد صالحاً، وتم تنظيفه من قاعدة البيانات.')
                        ->warning()
                        ->send();

                    return;
                }

                $errorMsg = $result->errors[0] ?? 'حدث خطأ غير معروف أثناء الاتصال بـ Firebase.';

                Notification::make()
                    ->title('فشل إرسال الإشعار التجريبي')
                    ->body($errorMsg)
                    ->danger()
                    ->send();
            });
    }
}
