<?php

declare(strict_types=1);

namespace App\Http\Resources\Api;

use App\DTOs\UpdateEvaluationResultDTO;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AppUpdateResource extends JsonResource
{
    public function __construct(
        private readonly UpdateEvaluationResultDTO $evaluation
    ) {
        parent::__construct($evaluation->update);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $update = $this->evaluation->update;

        // إذا لم يكن الجهاز ضمن شريحة الإطلاق التدريجي (Rolling Update) ولا توجد حالة طوارئ، نخفي التحديث الجديد عنه حالياً
        $updateAvailable = $this->evaluation->hasNewerVersion && $this->evaluation->isEligibleForRollout;

        return [
            'version' => (string) $update->version,
            'store_url' => (string) $update->store_url,
            'is_force_update' => $this->evaluation->shouldForceUpdate,
            'release_notes' => $update->release_notes,
            'update_available' => $updateAvailable,
            'has_newer_version' => $this->evaluation->hasNewerVersion,
            'is_blocked_version' => $this->evaluation->isBlockedVersion,
            'force_reason' => $this->evaluation->forceReason,
            'rollout_percentage' => (int) $update->rollout_percentage,
        ];
    }
}
