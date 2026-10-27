<?php

// app/Models/AppUpdate.php

declare(strict_types=1);

namespace App\Models;

use App\Enums\OsType;
use App\Traits\InteractsWithActivityLog;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;
use Spatie\Activitylog\Support\LogOptions;

class AppUpdate extends Model
{
    use HasFactory, InteractsWithActivityLog;

    protected $fillable = [
        'client_app_id',
        'target_os',
        'version',
        'min_supported_version',
        'blocked_versions',
        'store_url',
        'is_force_update',
        'rollout_percentage',
        'is_active',
        'release_notes',
    ];

    protected $casts = [
        'target_os' => OsType::class,
        'blocked_versions' => 'array',
        'is_force_update' => 'boolean',
        'rollout_percentage' => 'integer',
        'is_active' => 'boolean',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('app_updates')
            ->logFillable()
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    /**
     * توليد مفتاح الكاش لأحدث إصدار نشط بناءً على التطبيق ونظام التشغيل.
     */
    public static function getCacheKey(int $clientAppId, ?OsType $osType = null): string
    {
        $osKey = $osType?->value ?? 'any';

        return "app_update:latest:{$clientAppId}:{$osKey}";
    }

    /**
     * مسح جميع مفاتيح كاش التحديثات الخاصة بتطبيق معين لجميع الأنظمة.
     */
    public static function flushCacheForClientApp(int $clientAppId): void
    {
        Cache::forget(self::getCacheKey($clientAppId, null));

        foreach (OsType::cases() as $osCase) {
            Cache::forget(self::getCacheKey($clientAppId, $osCase));
        }
    }

    protected static function booted(): void
    {
        static::saved(function (AppUpdate $update): void {
            self::flushCacheForClientApp((int) $update->client_app_id);
        });

        static::deleted(function (AppUpdate $update): void {
            self::flushCacheForClientApp((int) $update->client_app_id);
        });
    }

    public function clientApp(): BelongsTo
    {
        return $this->belongsTo(ClientApp::class);
    }
}
