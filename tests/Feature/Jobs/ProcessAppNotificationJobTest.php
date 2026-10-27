<?php

// tests/Feature/Jobs/ProcessAppNotificationJobTest.php

declare(strict_types=1);

namespace Tests\Feature\Jobs;

use App\Contracts\PushNotificationServiceInterface;
use App\DTOs\PushDeliveryResultDTO;
use App\Enums\NotificationActionType;
use App\Enums\NotificationStatus;
use App\Enums\NotificationTargetType;
use App\Enums\NotificationType;
use App\Enums\OsType;
use App\Jobs\ProcessAppNotificationJob;
use App\Models\AppNotification;
use App\Models\ClientApp;
use App\Models\Device;
use App\Services\TargetAudienceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Mockery\MockInterface;
use Tests\TestCase;

class ProcessAppNotificationJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_sends_multicast_batches_updates_metrics_and_cleans_invalid_tokens(): void
    {
        $app = ClientApp::factory()->create(['is_active' => true]);

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
            'type' => NotificationType::Promotional,
            'target_type' => NotificationTargetType::AllDevices,
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
        $job->handle($pushServiceMock, app(TargetAudienceService::class));

        $notification->refresh();

        $this->assertSame(NotificationStatus::SENT, $notification->status);
        $this->assertSame(1, $notification->sent_count);
        $this->assertSame(1, $notification->failed_count);
        $this->assertNotNull($notification->sent_at);

        // التأكد من بقاء الجهاز الصالح وحذف الجهاز صاحب التوكن المنتهي تلقائياً
        $this->assertDatabaseHas('devices', ['id' => $validDevice->id]);
        $this->assertDatabaseMissing('devices', ['id' => $expiredDevice->id]);
    }

    public function test_it_filters_segmented_devices_by_os_and_app_versions(): void
    {
        $app = ClientApp::factory()->create(['is_active' => true]);

        // جهاز مطابق للشروط (Android + v2.0.0)
        Device::factory()->create([
            'client_app_id' => $app->id,
            'fcm_token' => 'matched_android_v2_token',
            'os_type' => OsType::ANDROID,
            'app_version' => '2.0.0',
        ]);

        // جهاز غير مطابق (إصدار قديم v1.0.0)
        Device::factory()->create([
            'client_app_id' => $app->id,
            'fcm_token' => 'unmatched_android_v1_token',
            'os_type' => OsType::ANDROID,
            'app_version' => '1.0.0',
        ]);

        // جهاز غير مطابق (نظام iOS)
        Device::factory()->create([
            'client_app_id' => $app->id,
            'fcm_token' => 'unmatched_ios_v2_token',
            'os_type' => OsType::IOS,
            'app_version' => '2.0.0',
        ]);

        $notification = AppNotification::factory()->create([
            'client_app_id' => $app->id,
            'type' => NotificationType::SystemAlert,
            'target_type' => NotificationTargetType::Segmented,
            'target_os' => OsType::ANDROID,
            'target_app_versions' => ['2.0.0', '2.1.0'],
            'action_type' => NotificationActionType::DeepLink,
            'action_value' => '/updates/changelog',
            'status' => NotificationStatus::PROCESSING,
        ]);

        $pushServiceMock = $this->mock(PushNotificationServiceInterface::class, function (MockInterface $mock): void {
            $mock->shouldReceive('sendMulticast')
                ->once()
                ->withArgs(function (array $tokens, ?string $title, ?string $body, array $data): bool {
                    return $tokens === ['matched_android_v2_token']
                        && $data['notification_type'] === NotificationType::SystemAlert->value
                        && $data['action_type'] === NotificationActionType::DeepLink->value
                        && $data['action_value'] === '/updates/changelog';
                })
                ->andReturn(new PushDeliveryResultDTO(successCount: 1, failureCount: 0));
        });

        $job = new ProcessAppNotificationJob($notification);
        $job->handle($pushServiceMock, app(TargetAudienceService::class));

        $notification->refresh();
        $this->assertSame(NotificationStatus::SENT, $notification->status);
        $this->assertSame(1, $notification->sent_count);
    }

    public function test_it_targets_only_specific_device_ids_when_specific_devices_target_type_is_selected(): void
    {
        $app = ClientApp::factory()->create(['is_active' => true]);

        $targetDevice = Device::factory()->create([
            'client_app_id' => $app->id,
            'fcm_token' => 'specific_target_token',
        ]);

        Device::factory()->create([
            'client_app_id' => $app->id,
            'fcm_token' => 'other_ignored_token',
        ]);

        $notification = AppNotification::factory()->create([
            'client_app_id' => $app->id,
            'type' => NotificationType::Transactional,
            'target_type' => NotificationTargetType::SpecificDevices,
            'target_device_ids' => [$targetDevice->id],
            'status' => NotificationStatus::PROCESSING,
        ]);

        $pushServiceMock = $this->mock(PushNotificationServiceInterface::class, function (MockInterface $mock): void {
            $mock->shouldReceive('sendMulticast')
                ->once()
                ->withArgs(fn (array $tokens): bool => $tokens === ['specific_target_token'])
                ->andReturn(new PushDeliveryResultDTO(successCount: 1, failureCount: 0));
        });

        $job = new ProcessAppNotificationJob($notification);
        $job->handle($pushServiceMock, app(TargetAudienceService::class));

        $notification->refresh();
        $this->assertSame(NotificationStatus::SENT, $notification->status);
        $this->assertSame(1, $notification->sent_count);
    }

    public function test_it_sends_silent_data_notification_without_title_or_body(): void
    {
        $app = ClientApp::factory()->create(['is_active' => true]);

        Device::factory()->create([
            'client_app_id' => $app->id,
            'fcm_token' => 'silent_target_token',
        ]);

        $notification = AppNotification::factory()->create([
            'client_app_id' => $app->id,
            'type' => NotificationType::SilentData,
            'target_type' => NotificationTargetType::AllDevices,
            'title' => null,
            'body' => null,
            'payload_data' => ['sync_config' => 'true'],
            'status' => NotificationStatus::PROCESSING,
        ]);

        $pushServiceMock = $this->mock(PushNotificationServiceInterface::class, function (MockInterface $mock): void {
            $mock->shouldReceive('sendMulticast')
                ->once()
                ->withArgs(function (array $tokens, ?string $title, ?string $body, array $data): bool {
                    return $tokens === ['silent_target_token']
                        && $title === null
                        && $body === null
                        && $data['sync_config'] === 'true'
                        && $data['notification_type'] === NotificationType::SilentData->value;
                })
                ->andReturn(new PushDeliveryResultDTO(successCount: 1, failureCount: 0));
        });

        $job = new ProcessAppNotificationJob($notification);
        $job->handle($pushServiceMock, app(TargetAudienceService::class));

        $notification->refresh();
        $this->assertSame(NotificationStatus::SENT, $notification->status);
    }

    public function test_it_dispatches_to_fcm_topic_when_topic_target_type_is_used(): void
    {
        $app = ClientApp::factory()->create(['is_active' => true]);

        $notification = AppNotification::factory()->create([
            'client_app_id' => $app->id,
            'type' => NotificationType::General,
            'target_type' => NotificationTargetType::Topic,
            'topic' => 'vip_offers',
            'title' => 'عرض VIP',
            'body' => 'حصري للمشتركين',
            'status' => NotificationStatus::PROCESSING,
        ]);

        $pushServiceMock = $this->mock(PushNotificationServiceInterface::class, function (MockInterface $mock): void {
            $mock->shouldReceive('sendToTopic')
                ->once()
                ->withArgs(fn (string $topic, ?string $title): bool => $topic === 'vip_offers' && $title === 'عرض VIP')
                ->andReturn(new PushDeliveryResultDTO(successCount: 1, failureCount: 0));
        });

        $job = new ProcessAppNotificationJob($notification);
        $job->handle($pushServiceMock, app(TargetAudienceService::class));

        $notification->refresh();
        $this->assertSame(NotificationStatus::SENT, $notification->status);
        $this->assertSame(1, $notification->sent_count);
    }

    public function test_scheduled_command_marks_due_notifications_as_processing_to_prevent_duplicate_dispatch(): void
    {
        Queue::fake();

        $app = ClientApp::factory()->create(['is_active' => true]);

        $dueNotification = AppNotification::factory()->create([
            'client_app_id' => $app->id,
            'status' => NotificationStatus::PENDING,
            'scheduled_at' => now()->subMinute(),
        ]);

        // التشغيل الأول للأمر المجدول
        $this->artisan('notifications:dispatch-scheduled')
            ->assertExitCode(0);

        $this->assertSame(NotificationStatus::PROCESSING, $dueNotification->fresh()?->status);
        Queue::assertPushed(ProcessAppNotificationJob::class, 1);

        // التشغيل الثاني في الدقيقة التالية لا يجب أن يدفع نفس الإشعار مرة أخرى
        $this->artisan('notifications:dispatch-scheduled')
            ->assertExitCode(0);

        Queue::assertPushed(ProcessAppNotificationJob::class, 1);
    }
}
