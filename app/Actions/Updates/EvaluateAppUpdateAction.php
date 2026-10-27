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
    /**
     * تقييم حالة التحديث لجهاز العميل مع تطبيق الكاش وقواعد الحظر والإطلاق التدريجي والصيانة.
     */
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
                // إعطاء الأولوية للتحديث المخصص لنظام التشغيل ثم الأحدث زمنياً
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

        // 1. التحقق مما إذا كان هناك إصدار أحدث فعلياً
        $hasNewerVersion = $currentVersion !== null && $currentVersion !== ''
            ? version_compare((string) $latestUpdate->version, $currentVersion, '>')
            : true;

        // 2. التحقق مما إذا كان إصدار المستخدم الحالي ضمن قائمة الإصدارات المحظورة (Blocked Versions)
        $blockedVersions = is_array($latestUpdate->blocked_versions)
            ? array_map('strval', $latestUpdate->blocked_versions)
            : [];

        $isBlockedVersion = $currentVersion !== null
            && in_array($currentVersion, $blockedVersions, true);

        // 3. التحقق مما إذا كان إصدار المستخدم أقدم من الحد الأدنى المسموح به (min_supported_version)
        $isBelowMinimumVersion = $currentVersion !== null
            && ! empty($latestUpdate->min_supported_version)
            && version_compare($currentVersion, (string) $latestUpdate->min_supported_version, '<');

        // 4. حساب أهلية الجهاز في التحديث التدريجي (Rolling Update) عبر Deterministic Hash (0 - 99)
        $rolloutPercentage = max(0, min(100, (int) ($latestUpdate->rollout_percentage ?? 100)));
        $isEligibleForRollout = $this->isDeviceInRolloutBucket(
            deviceIdentifier: $dto->deviceIdentifier,
            updateId: (int) $latestUpdate->id,
            percentage: $rolloutPercentage
        );

        // ملاحظة أمنية: إذا كان إصدار المستخدم محظوراً لثغرة أمنية أو دون الحد الأدنى، يتجاوز التحديث التدريجي فوراً
        if ($isBlockedVersion || $isBelowMinimumVersion) {
            $isEligibleForRollout = true;
        }

        // إذا لم يكن هناك إصدار أحدث، فلا نُجبر المستخدم على التحديث حتى لو كانت الراية مفعلة
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

        // 5. تحديد القرار النهائي لإجبار التحديث (Smart Force Update)
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
     * خوارزمية Deterministic Hashing لتوزيع الأجهزة بثبات بين 1% و 100%.
     */
    private function isDeviceInRolloutBucket(string $deviceIdentifier, int $updateId, int $percentage): bool
    {
        if ($percentage >= 100) {
            return true;
        }

        if ($percentage <= 0) {
            return false;
        }

        // توليد رقم ثابت بين 0 و 99 لكل جهاز مع هذا التحديث تحديداً
        $hashBucket = abs(crc32($deviceIdentifier.':update:'.$updateId)) % 100;

        return $hashBucket < $percentage;
    }
}
