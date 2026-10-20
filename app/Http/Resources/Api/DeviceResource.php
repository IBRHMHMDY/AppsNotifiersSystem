<?php

declare(strict_types=1);

namespace App\Http\Resources\Api;

use App\Models\Device;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Device
 */
class DeviceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'device_id' => $this->id,
            'subscribed_topics' => $this->subscribed_topics ?? [],
            'last_active_at' => $this->last_active_at?->toDateTimeString() ?? now()->toDateTimeString(),
        ];
    }
}
