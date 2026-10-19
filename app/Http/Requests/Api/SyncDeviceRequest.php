<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use App\DTOs\DeviceRegistrationDTO;
use App\Enums\OsType;
use App\Models\ClientApp;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SyncDeviceRequest extends FormRequest
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
            'fcm_token' => ['required', 'string', 'min:10', 'max:512'],
            'os_type' => ['required', Rule::enum(OsType::class)],
            'app_version' => ['required', 'string', 'max:50'],
            'topics' => ['nullable', 'array', 'max:25'],
            'topics.*' => ['string', 'max:64', 'regex:/^[a-zA-Z0-9-_.~%]+$/'],
        ];
    }

    public function clientApp(): ClientApp
    {
        /** @var ClientApp $clientApp */
        $clientApp = $this->attributes->get('clientApp');

        return $clientApp;
    }

    public function toDto(): DeviceRegistrationDTO
    {
        /** @var array{fcm_token: string, os_type: string, app_version: string, topics?: array<int, string>|null} $validated */
        $validated = $this->validated();
        $clientApp = $this->clientApp();

        return new DeviceRegistrationDTO(
            appKey: (string) $clientApp->app_key,
            fcmToken: $validated['fcm_token'],
            osType: OsType::from($validated['os_type']),
            appVersion: $validated['app_version'],
            clientAppId: (int) $clientApp->id,
            topics: isset($validated['topics']) ? array_values(array_unique($validated['topics'])) : null,
        );
    }
}