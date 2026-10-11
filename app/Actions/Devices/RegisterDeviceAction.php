<?php

declare(strict_types=1);

namespace App\Actions\Devices;

use App\DTOs\DeviceRegistrationDTO;
use App\Models\ClientApp;
use App\Models\Device;

class RegisterDeviceAction
{
    /**
     * تسجيل هاتف جديد أو تحديث بياناته إذا كان موجوداً مسبقاً.
     */
    public function execute(DeviceRegistrationDTO $dto): Device
    {
        // 1. جلب التطبيق بناءً على المفتاح السري (App Key)
        $clientApp = ClientApp::where('app_key', $dto->appKey)
            ->where('is_active', true)
            ->firstOrFail();

        // 2. تحديث التوكن أو إنشائه
        // نستخدم updateOrCreate لتحديث إصدار التطبيق وآخر ظهور إذا كان التوكن مسجلاً مسبقاً
        return Device::updateOrCreate(
            [
                'client_app_id' => $clientApp->id,
                'fcm_token' => $dto->fcmToken,
            ],
            [
                'os_type' => $dto->osType,
                'app_version' => $dto->appVersion,
                'last_active_at' => now(),
            ]
        );
    }
}