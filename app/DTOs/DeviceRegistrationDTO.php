<?php
// app/DTOs/DeviceRegistrationDTO.php

declare(strict_types=1);

namespace App\DTOs;

use App\Enums\OsType;

final readonly class DeviceRegistrationDTO
{
    public function __construct(
        public int $clientAppId,
        public string $fcmToken,
        public OsType $osType,
        public string $appVersion,
        public string $deviceId,
        public ?string $deviceModel = null,
    ) {}
}