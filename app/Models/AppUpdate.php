<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\OsType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class AppUpdate extends Model
{
    use HasFactory, LogsActivity;

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

    public function clientApp(): BelongsTo
    {
        return $this->belongsTo(ClientApp::class);
    }
}
