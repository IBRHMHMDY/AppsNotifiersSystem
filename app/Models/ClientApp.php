<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class ClientApp extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'bundle_id',
        'app_key',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * الحصول على مفتاح الكاش الموحد الخاص بمفتاح التطبيق.
     */
    public static function getCacheKey(string $appKey): string
    {
        return 'client_app_key:' . hash('sha256', $appKey);
    }

    /**
     * مسح الكاش المرتبط بالتطبيق عند التحديث أو الحذف لضمان أمان فوري.
     */
    public function flushAppKeyCache(): void
    {
        if (! empty($this->app_key)) {
            Cache::forget(self::getCacheKey((string) $this->app_key));
        }

        $originalAppKey = $this->getOriginal('app_key');
        if (! empty($originalAppKey) && $originalAppKey !== $this->app_key) {
            Cache::forget(self::getCacheKey((string) $originalAppKey));
        }
    }

    /**
     * توليد App Key تلقائي عند الإنشاء، ومسح الكاش عند أي تعديل أو حذف.
     */
    protected static function booted(): void
    {
        static::creating(function (ClientApp $clientApp): void {
            if (empty($clientApp->app_key)) {
                $clientApp->app_key = Str::random(40);
            }
        });

        static::saved(function (ClientApp $clientApp): void {
            $clientApp->flushAppKeyCache();
        });

        static::deleted(function (ClientApp $clientApp): void {
            $clientApp->flushAppKeyCache();
        });

        static::restored(function (ClientApp $clientApp): void {
            $clientApp->flushAppKeyCache();
        });
    }

    public function devices(): HasMany
    {
        return $this->hasMany(Device::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(AppNotification::class);
    }

    public function updates(): HasMany
    {
        return $this->hasMany(AppUpdate::class);
    }
}