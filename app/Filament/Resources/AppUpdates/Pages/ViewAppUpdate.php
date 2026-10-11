<?php

namespace App\Filament\Resources\AppUpdates\Pages;

use App\Filament\Resources\AppUpdates\AppUpdateResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewAppUpdate extends ViewRecord
{
    protected static string $resource = AppUpdateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
