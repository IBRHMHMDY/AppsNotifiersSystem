<?php

namespace App\Filament\Resources\AppUpdates\Pages;

use App\Filament\Resources\AppUpdates\AppUpdateResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditAppUpdate extends EditRecord
{
    protected static string $resource = AppUpdateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
