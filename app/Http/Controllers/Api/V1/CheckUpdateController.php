<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Updates\EvaluateAppUpdateAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CheckUpdateRequest;
use App\Http\Resources\Api\V1\AppUpdateResource;
use Illuminate\Http\JsonResponse;

class CheckUpdateController extends Controller
{
    public function __construct(
        private readonly EvaluateAppUpdateAction $evaluateAppUpdateAction
    ) {}

    public function __invoke(CheckUpdateRequest $request): JsonResponse
    {
        $evaluation = $this->evaluateAppUpdateAction->execute($request->toDto());

        if (! $evaluation) {
            return $this->successResponse(
                data: null,
                message: 'No updates available.'
            );
        }

        return $this->successResponse(
            data: new AppUpdateResource($evaluation),
            message: 'Latest update fetched successfully.'
        );
    }
}
