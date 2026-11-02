<?php
// app/Actions/Updates/EvaluateAppUpdateAction.php

declare(strict_types=1);

namespace App\Actions\Updates;

use App\DTOs\CheckUpdateDTO;
use App\DTOs\UpdateEvaluationResultDTO;
use App\Models\ClientApp;
use App\Models\Device;
use Composer\Semver\Comparator;

final readonly class EvaluateAppUpdateAction
{
    public function execute(CheckUpdateDTO $dto): ?UpdateEvaluationResultDTO
    {
        $app = ClientApp::find($dto->clientAppId);
        if (!$app) {
            return null;
        }

        // تحديث آخر ظهور للجهاز لضمان تتبع النشاط بدقة
        $device = Device::where('client_app_id', $app->id)
            ->where('device_id', $dto->deviceId)
            ->first();
            
        if ($device) {
            $device->update(['last_active_at' => now()]);
        }

        $activeUpdate = $app->updates()
            ->where('target_os', $dto->osType->value)
            ->where('is_active', true)
            ->latest()
            ->first();

        if (! $activeUpdate) {
            return null;
        }

        $hasNewerVersion = Comparator::greaterThan($activeUpdate->version, $dto->appVersion);
        
        if (! $hasNewerVersion) {
            return null;
        }

        $isForceUpdate = (bool) $activeUpdate->is_force_update;
        $isEligibleForRollout = true;

        // التحقق من استهداف جهاز معين (Smart Update) باستخدام device_id
        if (! $isForceUpdate && $activeUpdate->target_devices && $device) {
            $targetedDeviceIds = is_array($activeUpdate->target_devices) 
                ? $activeUpdate->target_devices 
                : json_decode($activeUpdate->target_devices, true) ?? [];
                
            if (! in_array($device->device_id, $targetedDeviceIds)) {
                $isEligibleForRollout = false;
            }
        }

        if (! $isEligibleForRollout) {
            return null;
        }

        return new UpdateEvaluationResultDTO(
            update: $activeUpdate,
            hasNewerVersion: $hasNewerVersion,
            shouldForceUpdate: $isForceUpdate,
            isBlockedVersion: false,
            isBelowMinimumVersion: false,
            isEligibleForRollout: $isEligibleForRollout,
            forceReason: $isForceUpdate ? 'manual_flag' : 'none',
            isUnderMaintenance: false,
            maintenanceMessage: null,
        );
    }
}