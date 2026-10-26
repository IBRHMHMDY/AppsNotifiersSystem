<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Devices\RegisterDeviceAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\SyncDeviceRequest;
use App\Http\Resources\Api\V1\DeviceResource;
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
