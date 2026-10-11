<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\OsType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Device extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_app_id',
        'fcm_token',
        'os_type',
        'app_version',
        'last_active_at',
    ];

    protected $casts = [
        'os_type' => OsType::class,
        'last_active_at' => 'datetime',
    ];

    public function clientApp(): BelongsTo
    {
        return $this->belongsTo(ClientApp::class);
    }
}