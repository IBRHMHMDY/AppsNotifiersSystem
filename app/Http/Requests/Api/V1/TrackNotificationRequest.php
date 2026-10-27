<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use App\DTOs\TrackNotificationDTO;
use App\Enums\NotificationEvent;
use App\Models\ClientApp;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TrackNotificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->attributes->get('clientApp') instanceof ClientApp;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'notification_id' => ['required', 'integer', 'exists:app_notifications,id'],
            'event' => ['required', Rule::enum(NotificationEvent::class)],
            'fcm_token' => ['nullable', 'string', 'min:10', 'max:512'],
        ];
    }

    public function clientApp(): ClientApp
    {
        /** @var ClientApp $clientApp */
        $clientApp = $this->attributes->get('clientApp');

        return $clientApp;
    }

    public function toDto(): TrackNotificationDTO
    {
        /** @var array{notification_id: int|string, event: string, fcm_token?: string|null} $validated */
        $validated = $this->validated();

        return new TrackNotificationDTO(
            clientAppId: (int) $this->clientApp()->id,
            notificationId: (int) $validated['notification_id'],
            event: NotificationEvent::from($validated['event']),
            fcmToken: $validated['fcm_token'] ?? null,
        );
    }
}
