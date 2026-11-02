<?php
// app/Http/Requests/Api/V1/TrackNotificationRequest.php

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
        return $this->has('client_app_id');
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            // تعديل النوع إلى string والبحث في حقل remote_id بدلاً من id المحلي
            'notification_id' => ['required', 'string', 'exists:app_notifications,remote_id'],
            'event' => ['required', Rule::enum(NotificationEvent::class)],
            'device_id' => ['required', 'string', 'max:255'],
        ];
    }

    public function clientApp(): ClientApp
    {
        return ClientApp::findOrFail($this->input('client_app_id'));
    }

    public function toDto(): TrackNotificationDTO
    {
        /** @var array{notification_id: string, event: string, fcm_token?: string|null} $validated */
        $validated = $this->validated();

        return new TrackNotificationDTO(
            clientAppId: (int) $this->clientApp()->id,
            // تمرير المعرف النصي إلى DTO
            notificationId: $validated['notification_id'], 
            event: NotificationEvent::from($validated['event']),
            deviceId: $validated['device_id'],
        );
    }
}