<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\OsType;
use App\Models\ClientApp;
use App\Models\Device;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Device>
 */
class DeviceFactory extends Factory
{
    protected $model = Device::class;

    public function definition(): array
    {
        return [
            'client_app_id' => ClientApp::factory(),
            'fcm_token' => 'fcm_token_'.Str::random(120),
            'os_type' => fake()->randomElement(OsType::cases()),
            'app_version' => '1.0.0',
            'subscribed_topics' => ['general'],
            'last_active_at' => now(),
        ];
    }
}
