<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AppUpdate;
use App\Models\ClientApp;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AppUpdate>
 */
class AppUpdateFactory extends Factory
{
    protected $model = AppUpdate::class;

    public function definition(): array
    {
        return [
            'client_app_id' => ClientApp::factory(),
            'target_os' => null,
            'version' => '2.0.0',
            'min_supported_version' => '1.2.0',
            'blocked_versions' => [],
            'store_url' => 'https://play.google.com/store/apps/details?id=com.example.app',
            'is_force_update' => false,
            'rollout_percentage' => 100,
            'is_active' => true,
            'release_notes' => 'تحسينات في الأداء وإصلاح الأخطاء.',
        ];
    }
}
