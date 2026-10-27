<?php

// app/Models/Device.php

declare(strict_types=1);

namespace App\Models;

use App\Enums\OsType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Device extends Model
{
    use HasFactory, MassPrunable;

    protected $fillable = [
        'client_app_id',
        'fcm_token',
        'os_type',
        'app_version',
        'subscribed_topics',
        'last_active_at',
    ];

    protected $casts = [
        'os_type' => OsType::class,
        'subscribed_topics' => 'array',
        'last_active_at' => 'datetime',
    ];

    /**
     * تحديد استعلام الحذف التلقائي للأجهزة الخاملة منذ أكثر من 180 يوماً.
     *
     * @return Builder<self>
     */
    public function prunable(): Builder
    {
        return static::query()
            ->whereNotNull('last_active_at')
            ->where('last_active_at', '<=', now()->subDays(180));
    }

    public function clientApp(): BelongsTo
    {
        return $this->belongsTo(ClientApp::class);
    }
}
