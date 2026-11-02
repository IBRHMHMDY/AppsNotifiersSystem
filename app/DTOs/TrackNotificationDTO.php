<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Enums\NotificationEvent;

readonly class TrackNotificationDTO
{
    public function __construct(
        public int $clientAppId,
        public String $notificationId,
        public NotificationEvent $event,
        public string $deviceId,
    ) {}
}
