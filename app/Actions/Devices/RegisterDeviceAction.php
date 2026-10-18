<?php

declare(strict_types=1);

namespace App\Actions\Devices;

use App\DTOs\DeviceRegistrationDTO;
use App\Models\ClientApp;
use App\Models\Device;
use Illuminate\Support\Facades\DB;

class RegisterDeviceAction
{
    public function execute(DeviceRegistrationDTO $dto): Device
    {
        $clientAppId = $dto->clientAppId
            ?? ClientApp::query()->where('app_key', $dto->appKey)->value('id');

        return DB::transaction(function () use ($clientAppId, $dto): Device {
            return Device::query()->updateOrCreate(
                [
                    'client_app_id' => $clientAppId,
                    'fcm_token' => $dto->fcmToken,
                ],
                [
                    'os_type' => $dto->osType,
                    'app_version' => $dto->appVersion,
                    'last_active_at' => now(),
                ]
            );
        });
    }
}