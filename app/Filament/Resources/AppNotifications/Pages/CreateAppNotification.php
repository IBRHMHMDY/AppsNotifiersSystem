<?php

declare(strict_types=1);

namespace App\Filament\Resources\AppNotifications\Pages;

use App\Actions\Notifications\DispatchNotificationAction;
use App\Enums\NotificationActionType;
use App\Enums\NotificationStatus;
use App\Enums\NotificationTargetType;
use App\Enums\NotificationType;
use App\Filament\Resources\AppNotifications\AppNotificationResource;
use App\Models\AppNotification;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateAppNotification extends CreateRecord
{
    protected static string $resource = AppNotificationResource::class;

    /**
     * تنقية وتوحيد البيانات قبل الحفظ في قاعدة البيانات لمنع بقاء أي قيم معلقة من خيارات غير نشطة.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['status'] = NotificationStatus::PENDING;

        return self::sanitizeNotificationFormData($data);
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

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function sanitizeNotificationFormData(array $data): array
    {
        $type = $data['type'] instanceof NotificationType
            ? $data['type']
            : (NotificationType::tryFrom((string) ($data['type'] ?? '')) ?? NotificationType::General);

        $targetType = $data['target_type'] instanceof NotificationTargetType
            ? $data['target_type']
            : (NotificationTargetType::tryFrom((string) ($data['target_type'] ?? '')) ?? NotificationTargetType::AllDevices);

        $actionType = $data['action_type'] instanceof NotificationActionType
            ? $data['action_type']
            : (NotificationActionType::tryFrom((string) ($data['action_type'] ?? '')) ?? NotificationActionType::OpenApp);

        // إذا كان الإشعار صامتاً، نصفر الحقول المرئية وإجراءات النقر
        if ($type->isSilent()) {
            $data['title'] = null;
            $data['body'] = null;
            $data['action_type'] = NotificationActionType::OpenApp->value;
            $data['action_value'] = null;
        } elseif (! $actionType->requiresValue()) {
            $data['action_value'] = null;
        }

        // تصفير حقول الاستهداف غير المرتبطة بالطريقة المختارة
        match ($targetType) {
            NotificationTargetType::AllDevices => (function () use (&$data): void {
                $data['topic'] = null;
                $data['target_os'] = null;
                $data['target_app_versions'] = null;
                $data['target_device_ids'] = null;
            })(),
            NotificationTargetType::Topic => (function () use (&$data): void {
                $data['topic'] = isset($data['topic']) ? ltrim(trim((string) $data['topic']), '#') : null;
                $data['target_os'] = null;
                $data['target_app_versions'] = null;
                $data['target_device_ids'] = null;
            })(),
            NotificationTargetType::Segmented => (function () use (&$data): void {
                $data['topic'] = null;
                $data['target_device_ids'] = null;
            })(),
            NotificationTargetType::SpecificDevices => (function () use (&$data): void {
                $data['topic'] = null;
                $data['target_os'] = null;
                $data['target_app_versions'] = null;
            })(),
        };

        return $data;
    }
}