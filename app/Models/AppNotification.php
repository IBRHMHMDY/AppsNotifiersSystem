<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\NotificationStatus;
use App\Enums\OsType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class AppNotification extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $fillable = [
        'client_app_id',
        'title',
        'body',
        'payload_data',
        'status',
        'target_os',
        'topic',
        'sent_count',
        'failed_count',
        'delivered_count',
        'opened_count',
        'clicked_count',
        'scheduled_at',
        'sent_at',
    ];

    protected $casts = [
        'payload_data' => 'array',
        'status' => NotificationStatus::class,
        'target_os' => OsType::class,
        'sent_count' => 'integer',
        'failed_count' => 'integer',
        'delivered_count' => 'integer',
        'opened_count' => 'integer',
        'clicked_count' => 'integer',
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

    public function clientApp(): BelongsTo
    {
        return $this->belongsTo(ClientApp::class);
    }

    /**
     * تسجيل مجموعة صور الإشعار.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('notification_image')
            ->singleFile();
    }
}