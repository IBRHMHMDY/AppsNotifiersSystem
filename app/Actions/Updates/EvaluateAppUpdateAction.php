<?php
// app/Actions/Updates/EvaluateAppUpdateAction.php

declare(strict_types=1);

namespace App\Actions\Updates;

use App\DTOs\CheckUpdateDTO;
use App\DTOs\UpdateEvaluationResultDTO;
use App\Models\AppUpdate;
use App\Models\ClientApp;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

class EvaluateAppUpdateAction
{
    public function execute(CheckUpdateDTO $dto): ?UpdateEvaluationResultDTO
    {
        /** @var AppUpdate|null $latestUpdate */
        $latestUpdate = Cache::remember(
            AppUpdate::getCacheKey($dto->clientAppId, $dto->osType),
            now()->addHour(),
            static fn (): ?AppUpdate => AppUpdate::query()
                ->where('client_app_id', $dto->clientAppId)
                ->where('is_active', true)
                ->when(
                    $dto->osType !== null,
                    fn (Builder $q) => $q->where(function (Builder $sub) use ($dto): void {
                        $sub->where('target_os', $dto->osType->value)
                            ->orWhereNull('target_os');
                    })
                )
                ->orderByRaw('CASE WHEN target_os IS NOT NULL THEN 0 ELSE 1 END')
                ->latest('created_at')
                ->first()
        );

        if (! $latestUpdate) {
            return null;
        }

        /** @var ClientApp|null $clientApp */
        $clientApp = ClientApp::query()->find($dto->clientAppId);
        $isUnderMaintenance = (bool) ($clientApp?->is_under_maintenance ?? false);
        $maintenanceMessage = $clientApp?->maintenance_message;

        $currentVersion = $dto->currentVersion !== null ? trim($dto->currentVersion) : null;

        $hasNewerVersion = $currentVersion !== null && $currentVersion !== ''
            ? version_compare((string) $latestUpdate->version, $currentVersion, '>')
            : true;

        $blockedVersions = is_array($latestUpdate->blocked_versions)
            ? array_map('strval', $latestUpdate->blocked_versions)
            : [];

        $isBlockedVersion = $currentVersion !== null
            && in_array($currentVersion, $blockedVersions, true);

        $isBelowMinimumVersion = $currentVersion !== null
            && ! empty($latestUpdate->min_supported_version)
            && version_compare($currentVersion, (string) $latestUpdate->min_supported_version, '<');

        $rolloutPercentage = max(0, min(100, (int) ($latestUpdate->rollout_percentage ?? 100)));
        
        // هنا تم الحل: تمرير المتغير حتى لو كان null، والدالة بالأسفل ستعوضه
        $isEligibleForRollout = $this->isDeviceInRolloutBucket(
            deviceIdentifier: $dto->deviceIdentifier,
            updateId: (int) $latestUpdate->id,
            percentage: $rolloutPercentage
        );

        if ($isBlockedVersion || $isBelowMinimumVersion) {
            $isEligibleForRollout = true;
        }

        if (! $hasNewerVersion) {
            return new UpdateEvaluationResultDTO(
                update: $latestUpdate,
                hasNewerVersion: false,
                shouldForceUpdate: false,
                isBlockedVersion: false,
                isBelowMinimumVersion: false,
                isEligibleForRollout: $isEligibleForRollout,
                forceReason: 'none',
                isUnderMaintenance: $isUnderMaintenance,
                maintenanceMessage: $maintenanceMessage,
            );
        }

        $shouldForceUpdate = false;
        $forceReason = 'none';

        if ($isBlockedVersion) {
            $shouldForceUpdate = true;
            $forceReason = 'blocked_version';
        } elseif ($isBelowMinimumVersion) {
            $shouldForceUpdate = true;
            $forceReason = 'below_min_version';
        } elseif ($latestUpdate->is_force_update && $isEligibleForRollout) {
            $shouldForceUpdate = true;
            $forceReason = 'manual_flag';
        }

        return new UpdateEvaluationResultDTO(
            update: $latestUpdate,
            hasNewerVersion: $hasNewerVersion,
            shouldForceUpdate: $shouldForceUpdate,
            isBlockedVersion: $isBlockedVersion,
            isBelowMinimumVersion: $isBelowMinimumVersion,
            isEligibleForRollout: $isEligibleForRollout,
            forceReason: $forceReason,
            isUnderMaintenance: $isUnderMaintenance,
            maintenanceMessage: $maintenanceMessage,
        );
    }

    /**
     * تم تعديل النوع ليقبل ?string لمنع خطأ TypeError
     */
    private function isDeviceInRolloutBucket(?string $deviceIdentifier, int $updateId, int $percentage): bool
    {
        if ($percentage >= 100) {
            return true;
        }

        if ($percentage <= 0) {
            return false;
        }

        // استخدام الـ IP إذا لم يُرسل التطبيق معرّف الجهاز (null)
        $identifier = !empty($deviceIdentifier) ? $deviceIdentifier : (string) (request()->ip() ?? 'unknown-device');
        
        $hashBucket = abs(crc32($identifier.':update:'.$updateId)) % 100;

        return $hashBucket < $percentage;
    }
}