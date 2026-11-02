<?php
// app/DTOs/CheckUpdateDTO.php

declare(strict_types=1);

namespace App\DTOs;

use App\Enums\OsType;

final readonly class CheckUpdateDTO
{
    public function __construct(
        public int $clientAppId,
        public string $appVersion,
        public OsType $osType,
        public string $deviceId,
    ) {}
}