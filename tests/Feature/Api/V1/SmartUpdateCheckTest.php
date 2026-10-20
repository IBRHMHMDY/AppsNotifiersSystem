<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\AppUpdate;
use App\Models\ClientApp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SmartUpdateCheckTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_forces_update_for_blocked_versions_even_when_global_force_is_false(): void
    {
        $app = ClientApp::factory()->create();

        AppUpdate::factory()->create([
            'client_app_id' => $app->id,
            'version' => '2.0.0',
            'min_supported_version' => '1.0.0',
            'blocked_versions' => ['1.3.2'],
            'is_force_update' => false,
            'rollout_percentage' => 10, // حتى مع نسبة إطلاق منخفضة، الإصدار المحظور يُجبر فوراً
        ]);

        // مستخدم على الإصدار المحظور 1.3.2
        $responseBlocked = $this->withHeader('X-App-Key', $app->app_key)
            ->getJson(route('api.v1.updates.check', [
                'current_version' => '1.3.2',
                'device_id' => 'device-abc',
            ]));

        $responseBlocked->assertOk()
            ->assertJsonPath('data.is_force_update', true)
            ->assertJsonPath('data.is_blocked_version', true)
            ->assertJsonPath('data.force_reason', 'blocked_version');

        // مستخدم على إصدار سليم 1.5.0
        $responseSafe = $this->withHeader('X-App-Key', $app->app_key)
            ->getJson(route('api.v1.updates.check', [
                'current_version' => '1.5.0',
                'device_id' => 'device-abc',
            ]));

        $responseSafe->assertOk()
            ->assertJsonPath('data.is_force_update', false)
            ->assertJsonPath('data.is_blocked_version', false);
    }

    public function test_it_forces_update_when_client_is_below_minimum_supported_version(): void
    {
        $app = ClientApp::factory()->create();

        AppUpdate::factory()->create([
            'client_app_id' => $app->id,
            'version' => '2.5.0',
            'min_supported_version' => '2.0.0',
            'is_force_update' => false,
        ]);

        $response = $this->withHeader('X-App-Key', $app->app_key)
            ->getJson(route('api.v1.updates.check', [
                'current_version' => '1.8.0',
            ]));

        $response->assertOk()
            ->assertJsonPath('data.is_force_update', true)
            ->assertJsonPath('data.force_reason', 'below_min_version');
    }
}
