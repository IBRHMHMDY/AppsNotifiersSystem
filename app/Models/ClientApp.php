<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
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
     * توليد App Key تلقائي عند إنشاء التطبيق لأول مرة
     */
    protected static function booted(): void
    {
        static::creating(function (ClientApp $clientApp) {
            if (empty($clientApp->app_key)) {
                $clientApp->app_key = Str::random(40);
            }
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