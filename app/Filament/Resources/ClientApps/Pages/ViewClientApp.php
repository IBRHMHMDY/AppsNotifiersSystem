<?php

namespace App\Filament\Resources\ClientApps\Pages;

use App\Filament\Resources\ClientApps\ClientAppResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewClientApp extends ViewRecord
{
    protected static string $resource = ClientAppResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
