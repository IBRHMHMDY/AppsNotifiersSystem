<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Enums\OsType;
use App\Models\ClientApp;
use App\Models\Device;
use Filament\Widgets\ChartWidget;

class DevicesByOsChart extends ChartWidget
{
    protected ?string $heading = 'توزيع الأجهزة حسب نظام التشغيل';

    protected static ?int $sort = 2;

    protected ?string $maxHeight = '280px';

    public ?string $filter = 'all';

    protected function getFilters(): ?array
    {
        return ['all' => 'جميع التطبيقات'] + ClientApp::query()
            ->pluck('name', 'id')
            ->mapWithKeys(fn (string $name, int|string $id): array => [(string) $id => $name])
            ->all();
    }

    protected function getData(): array
    {
        $labels = [];
        $data = [];

        foreach (OsType::cases() as $osCase) {
            $labels[] = strtoupper($osCase->value);

            $count = Device::query()
                ->when(
                    $this->filter && $this->filter !== 'all',
                    fn ($query) => $query->where('client_app_id', (int) $this->filter)
                )
                ->where('os_type', $osCase->value)
                ->count();

            $data[] = $count;
        }

        return [
            'datasets' => [
                [
                    'label' => 'عدد الأجهزة',
                    'data' => $data,
                    'backgroundColor' => [
                        '#10b981', // Android / Primary
                        '#3b82f6', // iOS / Secondary
                        '#f59e0b', // Other
                        '#8b5cf6',
                    ],
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
