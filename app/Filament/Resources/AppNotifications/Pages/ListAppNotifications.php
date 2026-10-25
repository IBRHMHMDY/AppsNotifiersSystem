<?php

declare(strict_types=1);

namespace App\Filament\Resources\AppNotifications\Pages;

use App\Enums\NotificationStatus;
use App\Enums\NotificationType;
use App\Filament\Resources\AppNotifications\AppNotificationResource;
use App\Models\AppNotification;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListAppNotifications extends ListRecords
{
    protected static string $resource = AppNotificationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('إنشاء إشعار جديد')
                ->icon('heroicon-o-plus-circle'),
        ];
    }

    /**
     * تبويبات سريعة لتصفية الإشعارات حسب الحالة أو النوع مع عدادات فورية.
     *
     * @return array<string, Tab>
     */
    public function getTabs(): array
    {
        return [
            'all' => Tab::make('الكل')
                ->icon('heroicon-o-inbox-stack')
                ->badge(fn (): int => AppNotification::query()->count()),

            'pending' => Tab::make('قيد الانتظار / مجدول')
                ->icon('heroicon-o-clock')
                ->badgeColor('warning')
                ->badge(fn (): int => AppNotification::query()
                    ->where('status', NotificationStatus::PENDING)
                    ->count())
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', NotificationStatus::PENDING)),

            'sent' => Tab::make('تم الإرسال')
                ->icon('heroicon-o-check-circle')
                ->badgeColor('success')
                ->badge(fn (): int => AppNotification::query()
                    ->where('status', NotificationStatus::SENT)
                    ->count())
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', NotificationStatus::SENT)),

            'failed' => Tab::make('فشل الإرسال')
                ->icon('heroicon-o-x-circle')
                ->badgeColor('danger')
                ->badge(fn (): int => AppNotification::query()
                    ->where('status', NotificationStatus::FAILED)
                    ->count())
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', NotificationStatus::FAILED)),

            'promotional' => Tab::make('ترويجي / تسويقي')
                ->icon('heroicon-o-megaphone')
                ->badgeColor('info')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('type', NotificationType::Promotional)),

            'silent' => Tab::make('بيانات صامتة')
                ->icon('heroicon-o-code-bracket-square')
                ->badgeColor('gray')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('type', NotificationType::SilentData)),
        ];
    }
}