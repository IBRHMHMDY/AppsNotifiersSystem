<?php

declare(strict_types=1);

namespace Tests\Feature\Jobs;

use App\Contracts\PushNotificationServiceInterface;
use App\DTOs\PushDeliveryResultDTO;
use App\Enums\NotificationStatus;
use App\Jobs\ProcessAppNotificationJob;
use App\Models\AppNotification;
use App\Models\ClientApp;
use App\Models\Device;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class ProcessAppNotificationJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_sends_multicast_batches_updates_metrics_and_cleans_invalid_tokens(): void
    {
        $app = ClientApp::factory()->create();

        $validDevice = Device::factory()->create([
            'client_app_id' => $app->id,
            'fcm_token' => 'valid_token_1',
        ]);

        $expiredDevice = Device::factory()->create([
            'client_app_id' => $app->id,
            'fcm_token' => 'expired_token_2',
        ]);

        $notification = AppNotification::factory()->create([
            'client_app_id' => $app->id,
            'title' => 'عرض خاص',
            'body' => 'خصم 50% اليوم فقط',
            'status' => NotificationStatus::PROCESSING,
        ]);

        $pushServiceMock = $this->mock(PushNotificationServiceInterface::class, function (MockInterface $mock): void {
            $mock->shouldReceive('sendMulticast')
                ->once()
                ->andReturn(new PushDeliveryResultDTO(
                    successCount: 1,
                    failureCount: 1,
                    invalidTokens: ['expired_token_2'],
                    errors: ['Unregistered token']
                ));
        });

        $job = new ProcessAppNotificationJob($notification);
        $job->handle($pushServiceMock);

        $notification->refresh();

        $this->assertSame(NotificationStatus::SENT, $notification->status);
        $this->assertSame(1, $notification->sent_count);
        $this->assertSame(1, $notification->failed_count);
        $this->assertNotNull($notification->sent_at);

        // التأكد من بقاء الجهاز الصالح وحذف الجهاز صاحب التوكن المنتهي تلقائياً
        $this->assertDatabaseHas('devices', ['id' => $validDevice->id]);
        $this->assertDatabaseMissing('devices', ['id' => $expiredDevice->id]);
    }
}
