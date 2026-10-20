<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Devices\RegisterDeviceAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\SyncDeviceRequest;
use App\Http\Resources\Api\DeviceResource;
use Illuminate\Http\JsonResponse;

class SyncDeviceController extends Controller
{
    public function __construct(
        private readonly RegisterDeviceAction $registerDeviceAction
    ) {}

    public function __invoke(SyncDeviceRequest $request): JsonResponse
    {
        $device = $this->registerDeviceAction->execute($request->toDto());

        return $this->successResponse(
            data: new DeviceResource($device),
            message: 'Device synced successfully.'
        );
    }
}
