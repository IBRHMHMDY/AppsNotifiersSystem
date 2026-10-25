<?php

declare(strict_types=1);

namespace App\Filament\Resources\AppNotifications\Pages;

use App\Actions\Notifications\DispatchNotificationAction;
use App\Enums\NotificationStatus;
use App\Filament\Resources\AppNotifications\AppNotificationResource;
use App\Models\AppNotification;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateAppNotification extends CreateRecord
{
    protected static string $resource = AppNotificationResource::class;

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['status'] = NotificationStatus::PENDING;

        return $data;
    }

    protected function afterCreate(): void
    {
        /** @var AppNotification $notification */
        $notification = $this->record;

        if ($notification->scheduled_at !== null && $notification->scheduled_at->isFuture()) {
            Notification::make()
                ->title('تمت جدولة الإشعار بنجاح')
                ->body('سيتم إرسال الإشعار تلقائياً في: ' . $notification->scheduled_at->format('Y-m-d h:i A'))
                ->info()
                ->send();

            return;
        }

        app(DispatchNotificationAction::class)->execute($notification, forceNow: true);
    }
}