<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Devices\RegisterDeviceAction;
use App\DTOs\DeviceRegistrationDTO;
use App\Enums\OsType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\SyncDeviceRequest;
use Illuminate\Http\JsonResponse;

class SyncDeviceController extends Controller
{
    public function __construct(
        private readonly RegisterDeviceAction $registerDeviceAction
    ) {}

    public function __invoke(SyncDeviceRequest $request): JsonResponse
    {
        $clientApp = $request->attributes->get('clientApp');
        $validated = $request->validated();

        $dto = new DeviceRegistrationDTO(
            appKey: $clientApp->app_key,
            fcmToken: $validated['fcm_token'],
            osType: OsType::from($validated['os_type']),
            appVersion: $validated['app_version']
        );

        $device = $this->registerDeviceAction->execute($dto);

        return response()->json([
            'success' => true,
            'message' => 'Device synced successfully.',
            'data' => [
                'device_id' => $device->id,
                'last_active_at' => $device->last_active_at->toDateTimeString(),
            ]
        ], 200);
    }
}