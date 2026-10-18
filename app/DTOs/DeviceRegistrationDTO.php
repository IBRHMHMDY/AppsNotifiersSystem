<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Enums\OsType;

readonly class DeviceRegistrationDTO
{
    public function __construct(
        public string $appKey, // مفتاح التطبيق القادم من Flutter
        public string $fcmToken,
        public OsType $osType,
        public string $appVersion,
        public ?int $clientAppId = null,
    ) {}
}