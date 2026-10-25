<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\NotificationTargetType;
use App\Enums\OsType;
use App\Models\AppNotification;
use App\Models\Device;
use Illuminate\Database\Eloquent\Builder;

class TargetAudienceService
{
    /**
     * بناء استعلام Eloquent للأجهزة المستهدفة بناءً على سجل الإشعار المحفوظ.
     *
     * @return Builder<Device>
     */
    public function buildQueryForNotification(AppNotification $notification): Builder
    {
        return $this->buildQueryFromCriteria(
            targetType: $notification->target_type ?? NotificationTargetType::AllDevices,
            clientAppId: $notification->client_app_id,
            osType: $notification->target_os,
            topic: $notification->topic,
            appVersions: $notification->target_app_versions,
            deviceIds: $notification->target_device_ids,
        );
    }

    /**
     * بناء استعلام الأجهزة بناءً على معايير حية (يُستخدم في كل من الـ Job ونموذج Filament اللحظي).
     *
     * @param  array<int, string>|null  $appVersions
     * @param  array<int, int|string>|null  $deviceIds
     * @return Builder<Device>
     */
    public function buildQueryFromCriteria(
        NotificationTargetType|string $targetType,
        ?int $clientAppId = null,
        OsType|string|null $osType = null,
        ?string $topic = null,
        ?array $appVersions = null,
        ?array $deviceIds = null,
    ): Builder {
        $resolvedTargetType = $targetType instanceof NotificationTargetType
            ? $targetType
            : (NotificationTargetType::tryFrom((string) $targetType) ?? NotificationTargetType::AllDevices);

        $resolvedOs = $osType instanceof OsType
            ? $osType
            : ($osType !== null && $osType !== '' ? OsType::tryFrom((string) $osType) : null);

        $query = Device::query()
            ->whereNotNull('fcm_token')
            ->where('fcm_token', '!=', '')
            ->when(
                $clientAppId !== null,
                fn (Builder $q): Builder => $q->where('client_app_id', $clientAppId),
                fn (Builder $q): Builder => $q->whereHas(
                    'clientApp',
                    fn (Builder $appQuery): Builder => $appQuery->where('is_active', true)
                )
            );

        return match ($resolvedTargetType) {
            NotificationTargetType::AllDevices => $query,

            NotificationTargetType::Topic => $topic !== null && trim($topic) !== ''
                ? $query->whereJsonContains('subscribed_topics', trim($topic))
                : $query->whereRaw('1 = 0'),

            NotificationTargetType::Segmented => $query
                ->when(
                    $resolvedOs !== null,
                    fn (Builder $q): Builder => $q->where('os_type', $resolvedOs->value)
                )
                ->when(
                    ! empty($appVersions),
                    fn (Builder $q): Builder => $q->whereIn('app_version', array_values($appVersions))
                ),

            NotificationTargetType::SpecificDevices => ! empty($deviceIds)
                ? $query->whereIn('id', array_map('intval', $deviceIds))
                : $query->whereRaw('1 = 0'),
        };
    }

    /**
     * حساب العدد التقديري للأجهزة المستهدفة لحظياً (للعرض في Filament Form).
     *
     * @param  array<int, string>|null  $appVersions
     * @param  array<int, int|string>|null  $deviceIds
     */
    public function estimateReachCount(
        NotificationTargetType|string $targetType,
        ?int $clientAppId = null,
        OsType|string|null $osType = null,
        ?string $topic = null,
        ?array $appVersions = null,
        ?array $deviceIds = null,
    ): int {
        return $this->buildQueryFromCriteria(
            targetType: $targetType,
            clientAppId: $clientAppId,
            osType: $osType,
            topic: $topic,
            appVersions: $appVersions,
            deviceIds: $deviceIds,
        )->count();
    }

    /**
     * استخراج قائمة بإصدارات التطبيق المسجلة فعلياً في الأجهزة لتسهيل الاختيار في Filament.
     *
     * @return array<string, string>
     */
    public function getAvailableAppVersions(?int $clientAppId = null): array
    {
        return Device::query()
            ->whereNotNull('app_version')
            ->where('app_version', '!=', '')
            ->when(
                $clientAppId !== null,
                fn (Builder $q): Builder => $q->where('client_app_id', $clientAppId)
            )
            ->distinct()
            ->orderByDesc('app_version')
            ->pluck('app_version', 'app_version')
            ->all();
    }

    /**
     * استخراج قائمة المواضيع (Topics) المشترك بها فعلياً في الأجهزة لتسهيل الاختيار في Filament.
     *
     * @return array<string, string>
     */
    public function getAvailableTopics(?int $clientAppId = null): array
    {
        $topicsList = Device::query()
            ->whereNotNull('subscribed_topics')
            ->when(
                $clientAppId !== null,
                fn (Builder $q): Builder => $q->where('client_app_id', $clientAppId)
            )
            ->limit(1000)
            ->pluck('subscribed_topics')
            ->flatten()
            ->filter(fn (mixed $topic): bool => is_string($topic) && trim($topic) !== '')
            ->map(fn (string $topic): string => trim($topic))
            ->unique()
            ->sort()
            ->values();

        return $topicsList->mapWithKeys(fn (string $topic): array => [$topic => "#{$topic}"])->all();
    }
}