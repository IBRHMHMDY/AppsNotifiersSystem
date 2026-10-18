<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CheckUpdateRequest;
use App\Http\Resources\Api\AppUpdateResource;
use App\Models\AppUpdate;
use Illuminate\Http\JsonResponse;

class CheckUpdateController extends Controller
{
    public function __invoke(CheckUpdateRequest $request): JsonResponse
    {
        $dto = $request->toDto();

        /** @var AppUpdate|null $latestUpdate */
        $latestUpdate = $request->clientApp()
            ->updates()
            ->latest('created_at')
            ->first();

        if (! $latestUpdate) {
            return $this->successResponse(
                data: null,
                message: 'No updates available.'
            );
        }

        return $this->successResponse(
            data: new AppUpdateResource($latestUpdate, $dto->currentVersion),
            message: 'Latest update fetched successfully.'
        );
    }
}