<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Notifications\TrackNotificationAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\TrackNotificationRequest;
use Illuminate\Http\JsonResponse;

class TrackNotificationController extends Controller
{
    public function __construct(
        private readonly TrackNotificationAction $trackNotificationAction
    ) {}

    public function __invoke(TrackNotificationRequest $request): JsonResponse
    {
        $tracked = $this->trackNotificationAction->execute($request->toDto());

        if (! $tracked) {
            return $this->errorResponse(
                message: 'Notification not found for this client application.',
                statusCode: 404
            );
        }

        return $this->successResponse(
            data: null,
            message: 'Notification event tracked successfully.'
        );
    }
}
