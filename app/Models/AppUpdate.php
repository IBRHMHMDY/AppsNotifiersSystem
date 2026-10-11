<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppUpdate extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_app_id',
        'version',
        'store_url',
        'is_force_update',
        'release_notes',
    ];

    protected $casts = [
        'is_force_update' => 'boolean',
    ];

    public function clientApp(): BelongsTo
    {
        return $this->belongsTo(ClientApp::class);
    }
}