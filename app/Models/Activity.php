<?php

// app/Models/Activity.php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Support\Collection;
use Spatie\Activitylog\Models\Activity as SpatieActivity;

class Activity extends SpatieActivity
{
    /**
     * إعادة التغييرات كـ Collection للتوافق التام مع حزم Filament و Spatie v4/v5.
     *
     * @return Collection<string, mixed>
     */
    public function changes(): Collection
    {
        $rawChanges = $this->getAttribute('attribute_changes')
            ?? $this->getAttribute('properties')
            ?? [];

        if ($rawChanges instanceof Collection) {
            return $rawChanges;
        }

        if (is_string($rawChanges)) {
            $decoded = json_decode($rawChanges, true);

            return collect(is_array($decoded) ? $decoded : []);
        }

        return collect(is_array($rawChanges) ? $rawChanges : []);
    }

    /**
     * دعم الوصول لـ $activity->changes كخاصية (Accessor) بجانب الدالة.
     *
     * @return Collection<string, mixed>
     */
    public function getChangesAttribute(): Collection
    {
        return $this->changes();
    }
}
