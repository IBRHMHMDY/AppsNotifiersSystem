<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Contracts\PushNotificationServiceInterface;
use App\Enums\NotificationEvent;
use App\Enums\NotificationStatus;
use App\Enums\OsType;
use App\Models\AppNotification;
use App\Models\ClientApp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class DeviceSyncAndTrackingTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_rejects_requests_without_valid_app_key(): void
    {
        $response = $this->postJson(route('api.v1.devices.sync'), []);

        $response->assertStatus(401)
            ->assertExactJson([
                'success' => false,
                'message' => 'Missing X-App-Key header.',
                'data' => null,
            ]);
    }

    public function test_it_rejects_inactive_client_app_even_if_cached(): void
    {
        $app = ClientApp::factory()->create(['is_active' => true]);

        // الطلب الأول يخزن التطبيق في الكاش
        $this->withHeader('X-App-Key', $app->app_key)
            ->getJson(route('api.v1.updates.check'))
            ->assertOk();

        // إيقاف التطبيق يمسح الكاش تلقائياً عبر booted()
        $app->update(['is_active' => false]);

        $this->withHeader('X-App-Key', $app->app_key)
            ->getJson(route('api.v1.updates.check'))
            ->assertStatus(401);
    }

    public function test_it_syncs_device_and_subscribes_to_firebase_topics(): void
    {
        $app = ClientApp::factory()->create();
        $firstOsCase = OsType::cases()[0]->value;

        $this->mock(PushNotificationServiceInterface::class, function (MockInterface $mock) use ($app): void {
            $mock->shouldReceive('subscribeToTopics')
                ->once()
                ->with(['offers', 'vip'], 'valid_fcm_token_1234567890', Mockery::on(fn ($passedApp) => $passedApp?->id === $app->id))
                ->andReturnTrue();
        });

        $response = $this->withHeader('X-App-Key', $app->app_key)
            ->postJson(route('api.v1.devices.sync'), [
                'fcm_token' => 'valid_fcm_token_1234567890',
                'os_type' => $firstOsCase,
                'app_version' => '1.2.0',
                'topics' => ['offers', 'vip'],
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.subscribed_topics', ['offers', 'vip']);

        $this->assertDatabaseHas('devices', [
            'client_app_id' => $app->id,
            'fcm_token' => 'valid_fcm_token_1234567890',
            'app_version' => '1.2.0',
        ]);
    }

    public function test_it_tracks_notification_events_atomically_and_prevents_duplicates_per_device(): void
    {
        $app = ClientApp::factory()->create();
        $notification = AppNotification::factory()->create([
            'client_app_id' => $app->id,
            'status' => NotificationStatus::SENT,
        ]);

        $payload = [
            'notification_id' => $notification->id,
            'event' => NotificationEvent::OPENED->value,
            'fcm_token' => 'device_token_unique_123456',
        ];

        // الإرسال الأول: يجب أن يزيد العداد بمقدار 1
        $this->withHeader('X-App-Key', $app->app_key)
            ->postJson(route('api.v1.notifications.track'), $payload)
            ->assertOk();

        // الإرسال الثاني المكرر لنفس الجهاز ونفس الحدث: يتم تجاهله بفضل Deduplication Cache
        $this->withHeader('X-App-Key', $app->app_key)
            ->postJson(route('api.v1.notifications.track'), $payload)
            ->assertOk();

        $this->assertSame(1, $notification->fresh()?->opened_count);
    }
}
