<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Enums\NotificationStatus;
use App\Enums\NotificationType;
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

        // حساب إجمالي الرسائل المرسلة للأجهزة ومعدل الفتح والنقر
        $metrics = AppNotification::query()
            ->where('status', NotificationStatus::SENT)
            ->where('type', '!=', NotificationType::SilentData)
            ->selectRaw('COALESCE(SUM(sent_count), 0) as total_sent, COALESCE(SUM(opened_count), 0) as total_opened, COALESCE(SUM(clicked_count), 0) as total_clicked')
            ->first();

        $totalSentMessages = (int) ($metrics->total_sent ?? 0);
        $totalOpenedMessages = (int) ($metrics->total_opened ?? 0);
        $totalClickedMessages = (int) ($metrics->total_clicked ?? 0);

        $overallCtr = $totalSentMessages > 0
            ? round(($totalOpenedMessages / $totalSentMessages) * 100, 1)
            : 0.0;

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

            Stat::make('حملات الإشعارات المرسلة', number_format($sentNotifications))
                ->description("معلق: {$pendingNotifications} | فاشل: {$failedNotifications}")
                ->descriptionIcon('heroicon-m-paper-airplane')
                ->color($failedNotifications > 0 ? 'warning' : 'info')
                ->chart($this->getLastSevenDaysTrend(
                    AppNotification::query()->where('status', NotificationStatus::SENT),
                    'sent_at'
                )),

            Stat::make('معدل التفاعل والفتح (CTR)', "{$overallCtr}%")
                ->description("فتح: " . number_format($totalOpenedMessages) . " | نقر: " . number_format($totalClickedMessages))
                ->descriptionIcon('heroicon-m-cursor-arrow-rays')
                ->color($overallCtr >= 10.0 ? 'success' : 'primary'),
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