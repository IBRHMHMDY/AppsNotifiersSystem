<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Enums\OsType;

readonly class DeviceRegistrationDTO
{
    /**
     * @param  array<int, string>|null  $topics
     */
    public function __construct(
        public string $appKey,
        public string $fcmToken,
        public OsType $osType,
        public string $appVersion,
        public ?int $clientAppId = null,
        public ?array $topics = null,
    ) {}
}