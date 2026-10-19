<?php

declare(strict_types=1);

namespace App\Actions\Devices;

use App\Contracts\PushNotificationServiceInterface;
use App\DTOs\DeviceRegistrationDTO;
use App\Models\ClientApp;
use App\Models\Device;
use Illuminate\Support\Facades\DB;

class RegisterDeviceAction
{
    public function __construct(
        private readonly PushNotificationServiceInterface $pushService
    ) {}

    public function execute(DeviceRegistrationDTO $dto): Device
    {
        $clientAppId = $dto->clientAppId
            ?? ClientApp::query()->where('app_key', $dto->appKey)->value('id');

        $existingDevice = Device::query()
            ->where('client_app_id', $clientAppId)
            ->where('fcm_token', $dto->fcmToken)
            ->first();

        $oldTopics = is_array($existingDevice?->subscribed_topics)
            ? $existingDevice->subscribed_topics
            : [];

        $newTopics = $dto->topics !== null ? $dto->topics : $oldTopics;

        $device = DB::transaction(function () use ($clientAppId, $dto, $newTopics): Device {
            return Device::query()->updateOrCreate(
                [
                    'client_app_id' => $clientAppId,
                    'fcm_token' => $dto->fcmToken,
                ],
                [
                    'os_type' => $dto->osType,
                    'app_version' => $dto->appVersion,
                    'subscribed_topics' => $newTopics,
                    'last_active_at' => now(),
                ]
            );
        });

        // إذا أرسل التطبيق قائمة مواضيع، نقوم بمزامنة الاشتراكات الملغاة والجديدة مع Firebase
        if ($dto->topics !== null) {
            $topicsToSubscribe = array_values(array_diff($newTopics, $oldTopics));
            $topicsToUnsubscribe = array_values(array_diff($oldTopics, $newTopics));

            if ($topicsToSubscribe !== []) {
                $this->pushService->subscribeToTopics($topicsToSubscribe, $dto->fcmToken);
            }

            if ($topicsToUnsubscribe !== []) {
                $this->pushService->unsubscribeFromTopics($topicsToUnsubscribe, $dto->fcmToken);
            }
        }

        return $device;
    }
}