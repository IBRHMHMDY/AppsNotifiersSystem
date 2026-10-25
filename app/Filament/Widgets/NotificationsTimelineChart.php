<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Enums\NotificationStatus;
use App\Enums\NotificationTargetType;
use App\Enums\NotificationType;
use App\Models\AppNotification;
use Filament\Widgets\ChartWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class NotificationsTimelineChart extends ChartWidget
{
    protected ?string $heading = 'حركة الإشعارات حسب النوع والاستهداف (آخر 7 أيام)';

    protected static ?int $sort = 3;

    protected ?string $maxHeight = '280px';

    public ?string $filter = 'all';

    /**
     * فلاتر تفاعلية أعلى الرسم البياني لتحليل الإشعارات حسب النوع أو طريقة الاستهداف.
     *
     * @return array<string, string>
     */
    protected function getFilters(): ?array
    {
        return [
            'all' => 'جميع الإشعارات',
            'type:'.NotificationType::General->value => 'النوع: '.NotificationType::General->getLabel(),
            'type:'.NotificationType::Promotional->value => 'النوع: '.NotificationType::Promotional->getLabel(),
            'type:'.NotificationType::Transactional->value => 'النوع: '.NotificationType::Transactional->getLabel(),
            'type:'.NotificationType::SystemAlert->value => 'النوع: '.NotificationType::SystemAlert->getLabel(),
            'type:'.NotificationType::SilentData->value => 'النوع: '.NotificationType::SilentData->getLabel(),
            'target:'.NotificationTargetType::AllDevices->value => 'الاستهداف: '.NotificationTargetType::AllDevices->getLabel(),
            'target:'.NotificationTargetType::Topic->value => 'الاستهداف: '.NotificationTargetType::Topic->getLabel(),
            'target:'.NotificationTargetType::Segmented->value => 'الاستهداف: '.NotificationTargetType::Segmented->getLabel(),
            'target:'.NotificationTargetType::SpecificDevices->value => 'الاستهداف: '.NotificationTargetType::SpecificDevices->getLabel(),
        ];
    }

    protected function getData(): array
    {
        $labels = [];
        $sentSeries = [];
        $failedSeries = [];

        $startDate = Carbon::today()->subDays(6)->startOfDay();
        $activeFilter = $this->filter ?? 'all';

        $notifications = AppNotification::query()
            ->where('created_at', '>=', $startDate)
            ->when(
                str_starts_with($activeFilter, 'type:'),
                fn (Builder $q): Builder => $q->where('type', substr($activeFilter, 5))
            )
            ->when(
                str_starts_with($activeFilter, 'target:'),
                fn (Builder $q): Builder => $q->where('target_type', substr($activeFilter, 7))
            )
            ->get(['status', 'created_at']);

        for ($i = 6; $i >= 0; $i--) {
            $day = Carbon::today()->subDays($i);
            $labels[] = $day->format('m/d');

            $dayRecords = $notifications->filter(
                fn (AppNotification $item): bool => $item->created_at !== null && $item->created_at->isSameDay($day)
            );

            $sentSeries[] = $dayRecords->where('status', NotificationStatus::SENT)->count();
            $failedSeries[] = $dayRecords->where('status', NotificationStatus::FAILED)->count();
        }

        return [
            'datasets' => [
                [
                    'label' => 'تم الإرسال (Sent)',
                    'data' => $sentSeries,
                    'borderColor' => '#10b981',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.15)',
                    'fill' => true,
                    'tension' => 0.3,
                ],
                [
                    'label' => 'فشل الإرسال (Failed)',
                    'data' => $failedSeries,
                    'borderColor' => '#ef4444',
                    'backgroundColor' => 'rgba(239, 68, 68, 0.15)',
                    'fill' => true,
                    'tension' => 0.3,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
