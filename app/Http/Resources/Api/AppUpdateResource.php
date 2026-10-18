<?php

declare(strict_types=1);

namespace App\Http\Resources\Api;

use App\Models\AppUpdate;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin AppUpdate
 */
class AppUpdateResource extends JsonResource
{
    public function __construct(
        AppUpdate $resource,
        private readonly ?string $currentClientVersion = null
    ) {
        parent::__construct($resource);
    }

    /**
     * تحويل النموذج إلى مصفوفة JSON موحدة.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $hasNewerVersion = $this->currentClientVersion !== null
            ? version_compare((string) $this->version, $this->currentClientVersion, '>')
            : true;

        return [
            'version' => (string) $this->version,
            'store_url' => (string) $this->store_url,
            'is_force_update' => (bool) $this->is_force_update,
            'release_notes' => $this->release_notes,
            'has_newer_version' => $hasNewerVersion,
        ];
    }
}