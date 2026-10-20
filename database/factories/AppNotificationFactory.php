<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\NotificationStatus;
use App\Models\AppNotification;
use App\Models\ClientApp;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AppNotification>
 */
class AppNotificationFactory extends Factory
{
    protected $model = AppNotification::class;

    public function definition(): array
    {
        return [
            'client_app_id' => ClientApp::factory(),
            'title' => fake()->sentence(4),
            'body' => fake()->paragraph(2),
            'payload_data' => ['screen' => 'home'],
            'status' => NotificationStatus::PENDING,
            'target_os' => null,
            'topic' => null,
            'sent_count' => 0,
            'failed_count' => 0,
            'delivered_count' => 0,
            'opened_count' => 0,
            'clicked_count' => 0,
            'scheduled_at' => null,
            'sent_at' => null,
        ];
    }
}
