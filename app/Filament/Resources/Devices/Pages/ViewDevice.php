<?php

// app/Filament/Resources/Devices/Pages/ViewDevice.php

declare(strict_types=1);

namespace App\Filament\Resources\Devices\Pages;

use App\Filament\Resources\Devices\DeviceResource;
use App\Filament\Resources\Devices\Tables\DevicesTable;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\ViewRecord;

class ViewDevice extends ViewRecord
{
    protected static string $resource = DeviceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DevicesTable::makeSendTestPushAction(),
            DeleteAction::make(),
        ];
    }
}
