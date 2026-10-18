<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Enums\NotificationStatus;
use App\Models\AppNotification;
use App\Models\ClientApp;
use App\Models\Device;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class DashboardStats extends BaseWidget
{
    protected static ?int $sort = 1;

    protected ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        $totalApps = ClientApp::query()->count();
        $activeApps = ClientApp::query()->where('is_active', true)->count();

        $totalDevices = Device::query()->count();
        $activeDevicesLast7Days = Device::query()
            ->where('last_active_at', '>=', now()->subDays(7))
            ->count();

        $sentNotifications = AppNotification::query()
            ->where('status', NotificationStatus::SENT)
            ->count();
        $failedNotifications = AppNotification::query()
            ->where('status', NotificationStatus::FAILED)
            ->count();
        $pendingNotifications = AppNotification::query()
            ->whereIn('status', [NotificationStatus::PENDING, NotificationStatus::PROCESSING])
            ->count();

        return [
            Stat::make('إجمالي التطبيقات', (string) $totalApps)
                ->description("منها {$activeApps} تطبيق نشط حالياً")
                ->descriptionIcon('heroicon-m-device-phone-mobile')
                ->color('primary')
                ->chart($this->getLastSevenDaysTrend(ClientApp::query())),

            Stat::make('الأجهزة المسجلة', number_format($totalDevices))
                ->description("{$activeDevicesLast7Days} جهاز نشط خلال آخر 7 أيام")
                ->descriptionIcon('heroicon-m-users')
                ->color('success')
                ->chart($this->getLastSevenDaysTrend(Device::query())),

            Stat::make('الإشعارات المرسلة', number_format($sentNotifications))
                ->description("معلق: {$pendingNotifications} | فاشل: {$failedNotifications}")
                ->descriptionIcon('heroicon-m-paper-airplane')
                ->color($failedNotifications > 0 ? 'warning' : 'info')
                ->chart($this->getLastSevenDaysTrend(
                    AppNotification::query()->where('status', NotificationStatus::SENT),
                    'sent_at'
                )),
        ];
    }

    /**
     * حساب عدد السجلات اليومية لآخر 7 أيام لرسم المخطط المصغر (Sparkline) بشكل حقيقي.
     *
     * @return array<int, int>
     */
    private function getLastSevenDaysTrend(Builder $query, string $dateColumn = 'created_at'): array
    {
        $startDate = Carbon::today()->subDays(6)->startOfDay();

        $records = (clone $query)
            ->where($dateColumn, '>=', $startDate)
            ->get([$dateColumn])
            ->groupBy(fn ($model): string => Carbon::parse($model->{$dateColumn})->format('Y-m-d'));

        $trend = [];
        for ($i = 6; $i >= 0; $i--) {
            $dateKey = Carbon::today()->subDays($i)->format('Y-m-d');
            $trend[] = isset($records[$dateKey]) ? $records[$dateKey]->count() : 0;
        }

        return $trend;
    }
}