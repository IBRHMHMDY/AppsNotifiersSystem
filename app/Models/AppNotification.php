<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\NotificationActionType;
use App\Enums\NotificationStatus;
use App\Enums\NotificationTargetType;
use App\Enums\NotificationType;
use App\Enums\OsType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class AppNotification extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia, LogsActivity;

    protected $fillable = [
        'client_app_id',
        'type',
        'title',
        'body',
        'action_type',
        'action_value',
        'payload_data',
        'status',
        'target_type',
        'target_os',
        'topic',
        'target_app_versions',
        'target_device_ids',
        'sent_count',
        'failed_count',
        'delivered_count',
        'opened_count',
        'clicked_count',
        'scheduled_at',
        'sent_at',
    ];

    protected $casts = [
        'type' => NotificationType::class,
        'action_type' => NotificationActionType::class,
        'payload_data' => 'array',
        'status' => NotificationStatus::class,
        'target_type' => NotificationTargetType::class,
        'target_os' => OsType::class,
        'target_app_versions' => 'array',
        'target_device_ids' => 'array',
        'sent_count' => 'integer',
        'failed_count' => 'integer',
        'delivered_count' => 'integer',
        'opened_count' => 'integer',
        'clicked_count' => 'integer',
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('app_notifications')
            ->logOnly([
                'client_app_id',
                'type',
                'title',
                'status',
                'target_type',
                'target_os',
                'topic',
                'action_type',
                'scheduled_at',
            ])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    public function clientApp(): BelongsTo
    {
        return $this->belongsTo(ClientApp::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('notification_image')
            ->singleFile();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(200)
            ->height(200)
            ->nonQueued();
    }

    /**
     * إرجاع وصف مختصر وواضح لتفاصيل الاستهداف لعرضه في جداول وشاشات Filament.
     */
    public function getTargetSummaryAttribute(): string
    {
        return match ($this->target_type) {
            NotificationTargetType::AllDevices => $this->clientApp
                ? "كل أجهزة ({$this->clientApp->name})"
                : 'كل التطبيقات والأجهزة (بث عام)',

            NotificationTargetType::Topic => 'الموضوع: #'.($this->topic ?: 'غير محدد'),

            NotificationTargetType::Segmented => collect([
                $this->target_os ? "النظام: {$this->target_os->getLabel()}" : 'كل الأنظمة',
                ! empty($this->target_app_versions)
                    ? 'الإصدارات: '.implode(', ', $this->target_app_versions)
                    : null,
            ])->filter()->implode(' • '),

            NotificationTargetType::SpecificDevices => 'عدد الأجهزة المحددة: '.count($this->target_device_ids ?? []),

            default => 'غير محدد',
        };
    }
}
