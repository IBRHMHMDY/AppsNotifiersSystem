<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Enums\NotificationStatus;
use App\Models\AppNotification;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class NotificationsTimelineChart extends ChartWidget
{
    protected ?string $heading = 'حركة الإشعارات (آخر 7 أيام)';

    protected static ?int $sort = 3;

    protected ?string $maxHeight = '280px';

    protected function getData(): array
    {
        $labels = [];
        $sentSeries = [];
        $failedSeries = [];

        $startDate = Carbon::today()->subDays(6)->startOfDay();

        $notifications = AppNotification::query()
            ->where('created_at', '>=', $startDate)
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
