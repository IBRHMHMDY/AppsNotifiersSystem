<?php

// app/DTOs/UpdateEvaluationResultDTO.php

declare(strict_types=1);

namespace App\DTOs;

use App\Models\AppUpdate;

readonly class UpdateEvaluationResultDTO
{
    public function __construct(
        public AppUpdate $update,
        public bool $hasNewerVersion,
        public bool $shouldForceUpdate,
        public bool $isBlockedVersion,
        public bool $isBelowMinimumVersion,
        public bool $isEligibleForRollout,
        public string $forceReason = 'none', // none | manual_flag | blocked_version | below_min_version
        public bool $isUnderMaintenance = false,
        public ?string $maintenanceMessage = null,
    ) {}
}
