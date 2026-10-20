<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ClientApp;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ClientApp>
 */
class ClientAppFactory extends Factory
{
    protected $model = ClientApp::class;

    public function definition(): array
    {
        return [
            'name' => fake()->company().' App',
            'bundle_id' => 'com.'.fake()->unique()->domainWord().'.app',
            'app_key' => Str::random(40),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => [
            'is_active' => false,
        ]);
    }
}
