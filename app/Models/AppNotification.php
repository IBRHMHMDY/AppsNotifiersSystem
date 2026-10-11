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
        'status',
        'target_os',
        'scheduled_at',
        'sent_at',
    ];

    protected $casts = [
        'status' => NotificationStatus::class,
        'target_os' => OsType::class,
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

    public function clientApp(): BelongsTo
    {
        return $this->belongsTo(ClientApp::class);
    }

    /**
     * تسجيل مجموعة صور الإشعار
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('notification_image')
            ->singleFile(); // الإشعار يحتوي على صورة واحدة كحد أقصى
    }
}
