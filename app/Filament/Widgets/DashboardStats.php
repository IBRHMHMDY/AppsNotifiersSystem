<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\AppNotification;
use App\Models\ClientApp;
use App\Models\Device;
use App\Enums\NotificationStatus;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DashboardStats extends BaseWidget
{
    // ترتيب الويدجت ليظهر في أعلى الصفحة الرئيسية
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        return [
            Stat::make('إجمالي التطبيقات', ClientApp::count())
                ->description('التطبيقات المسجلة والفعالة')
                ->descriptionIcon('heroicon-m-device-phone-mobile')
                ->color('primary')
                ->chart([7, 2, 10, 3, 15, 4, 17]), // رسم بياني جمالي تقريبي

            Stat::make('إجمالي الأجهزة', Device::count())
                ->description('هواتف المستخدمين المرتبطة')
                ->descriptionIcon('heroicon-m-users')
                ->color('success')
                ->chart([3, 12, 5, 20, 10, 25, 30]),

            Stat::make('الإشعارات الناجحة', AppNotification::where('status', NotificationStatus::SENT)->count())
                ->description('تم إرسالها بنجاح')
                ->descriptionIcon('heroicon-m-paper-airplane')
                ->color('info'),
        ];
    }
}