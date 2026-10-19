<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Enums\OsType;

readonly class CheckUpdateDTO
{
    public function __construct(
        public int $clientAppId,
        public string $deviceIdentifier,
        public ?string $currentVersion = null,
        public ?OsType $osType = null,
    ) {}
}