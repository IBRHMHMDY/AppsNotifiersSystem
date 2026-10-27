<?php

// app/Filament/Resources/Devices/Pages/ListDevices.php

declare(strict_types=1);

namespace App\Filament\Resources\Devices\Pages;

use App\Enums\OsType;
use App\Filament\Resources\Devices\DeviceResource;
use App\Models\Device;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListDevices extends ListRecords
{
    protected static string $resource = DeviceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('prune_stale_devices')
                ->label('تنظيف الأجهزة الخاملة (+90 يوم)')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('تنظيف الأجهزة القديمة غير النشطة')
                ->modalDescription('سيقوم هذا الإجراء بحذف الأجهزة التي لم تتصل بالمنصة منذ أكثر من 90 يوماً لتحسين سرعة قاعدة البيانات. هل تود المتابعة؟')
                ->action(function (): void {
                    $deletedCount = Device::query()
                        ->where('last_active_at', '<', now()->subDays(90))
                        ->delete();

                    Notification::make()
                        ->title("تم تنظيف {$deletedCount} جهاز خامل بنجاح")
                        ->success()
                        ->send();
                }),
        ];
    }

    /**
     * @return array<string, Tab>
     */
    public function getTabs(): array
    {
        return [
            'all' => Tab::make('الكل')
                ->icon('heroicon-o-cpu-chip')
                ->badge(fn (): int => Device::query()->count()),

            'active_7d' => Tab::make('نشط (آخر 7 أيام)')
                ->icon('heroicon-o-bolt')
                ->badgeColor('success')
                ->badge(fn (): int => Device::query()
                    ->where('last_active_at', '>=', now()->subDays(7))
                    ->count())
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('last_active_at', '>=', now()->subDays(7))),

            'android' => Tab::make('Android')
                ->icon('heroicon-o-device-phone-mobile')
                ->badgeColor('success')
                ->badge(fn (): int => Device::query()
                    ->where('os_type', OsType::ANDROID)
                    ->count())
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('os_type', OsType::ANDROID)),

            'ios' => Tab::make('iOS')
                ->icon('heroicon-o-device-phone-mobile')
                ->badgeColor('info')
                ->badge(fn (): int => Device::query()
                    ->where('os_type', OsType::IOS)
                    ->count())
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('os_type', OsType::IOS)),

            'stale' => Tab::make('خامل (+30 يوم)')
                ->icon('heroicon-o-clock')
                ->badgeColor('warning')
                ->badge(fn (): int => Device::query()
                    ->where('last_active_at', '<', now()->subDays(30))
                    ->count())
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('last_active_at', '<', now()->subDays(30))),
        ];
    }
}
