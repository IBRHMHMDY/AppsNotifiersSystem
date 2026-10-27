<?php

// app/Filament/Resources/Devices/DeviceResource.php

declare(strict_types=1);

namespace App\Filament\Resources\Devices;

use App\Filament\Resources\Devices\Pages\ListDevices;
use App\Filament\Resources\Devices\Pages\ViewDevice;
use App\Filament\Resources\Devices\Schemas\DeviceInfolist;
use App\Filament\Resources\Devices\Tables\DevicesTable;
use App\Models\Device;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class DeviceResource extends Resource
{
    protected static ?string $model = Device::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCpuChip;

    protected static string|UnitEnum|null $navigationGroup = 'إدارة النظام';

    protected static ?string $navigationLabel = 'الأجهزة المسجلة';

    protected static ?string $modelLabel = 'جهاز';

    protected static ?string $pluralModelLabel = 'الأجهزة المسجلة';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'fcm_token';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['clientApp']);
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) Device::query()->count();
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'success';
    }

    public static function infolist(Schema $schema): Schema
    {
        return DeviceInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DevicesTable::configure($table);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDevices::route('/'),
            'view' => ViewDevice::route('/{record}'),
        ];
    }
}
