<?php

namespace App\Filament\Resources\AppUpdates\Pages;

use App\Filament\Resources\AppUpdates\AppUpdateResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAppUpdates extends ListRecords
{
    protected static string $resource = AppUpdateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
