<?php

declare(strict_types=1);

namespace App\Filament\Resources\AppNotifications\Pages;

use App\Filament\Resources\AppNotifications\AppNotificationResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditAppNotification extends EditRecord
{
    protected static string $resource = AppNotificationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        return CreateAppNotification::sanitizeNotificationFormData($data);
    }
}