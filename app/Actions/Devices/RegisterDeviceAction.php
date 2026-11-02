<?php
// app/Actions/Devices/RegisterDeviceAction.php

declare(strict_types=1);

namespace App\Actions\Devices;

use App\DTOs\DeviceRegistrationDTO;
use App\Models\Device;

final readonly class RegisterDeviceAction
{
    public function execute(DeviceRegistrationDTO $dto): Device
    {
        return Device::updateOrCreate(
            [
                'client_app_id' => $dto->clientAppId,
                'device_id' => $dto->deviceId, // أصبح يعتمد على device_id وليس التوكن
            ],
            [
                'fcm_token' => $dto->fcmToken,
                'os_type' => $dto->osType,
                'app_version' => $dto->appVersion,
                'device_model' => $dto->deviceModel,
                'last_active_at' => now(),
            ]
        );
    }
}