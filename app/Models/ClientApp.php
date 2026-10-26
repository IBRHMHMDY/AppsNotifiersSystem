<?php

// app/Models/ClientApp.php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class ClientApp extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'name',
        'bundle_id',
        'app_key',
        'firebase_project_id',
        'firebase_credentials',
        'is_active',
    ];

    protected $hidden = [
        'firebase_credentials',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'firebase_credentials' => 'encrypted:array',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('client_apps')
            ->logOnly(['name', 'bundle_id', 'firebase_project_id', 'is_active'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    public function hasCustomFirebaseCredentials(): bool
    {
        return is_array($this->firebase_credentials)
            && ! empty($this->firebase_credentials['project_id'])
            && ! empty($this->firebase_credentials['private_key'])
            && ! empty($this->firebase_credentials['client_email']);
    }

    public static function getCacheKey(string $appKey): string
    {
        return 'client_app_key:'.hash('sha256', $appKey);
    }

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

    protected static function booted(): void
    {
        static::creating(function (ClientApp $clientApp): void {
            if (empty($clientApp->app_key)) {
                $clientApp->app_key = Str::random(40);
            }
        });

        static::saving(function (ClientApp $clientApp): void {
            if (is_array($clientApp->firebase_credentials) && ! empty($clientApp->firebase_credentials['project_id'])) {
                $clientApp->firebase_project_id = (string) $clientApp->firebase_credentials['project_id'];
            } else {
                $clientApp->firebase_project_id = null;
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
