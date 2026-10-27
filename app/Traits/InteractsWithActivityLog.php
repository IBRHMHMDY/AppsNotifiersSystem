<?php

// app/Traits/InteractsWithActivityLog.php

declare(strict_types=1);

namespace App\Traits;

use App\Models\Activity;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Spatie\Activitylog\Models\Concerns\LogsActivity;

trait InteractsWithActivityLog
{
    use LogsActivity;

    /**
     * تعريف علاقة activities() باستخدام موديل App\Models\Activity المخصص.
     *
     * @return MorphMany<Activity, $this>
     */
    public function activities(): MorphMany
    {
        return $this->morphMany(Activity::class, 'subject');
    }
}
