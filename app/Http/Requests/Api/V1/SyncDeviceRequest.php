<?php
// app/Http/Requests/Api/V1/SyncDeviceRequest.php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use App\DTOs\DeviceRegistrationDTO;
use App\Enums\OsType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SyncDeviceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'client_app_id' => ['required', 'integer'],
            'fcm_token' => ['required', 'string'],
            'os_type' => ['required', Rule::enum(OsType::class)],
            'app_version' => ['required', 'string', 'max:50'],
            'device_id' => ['required', 'string', 'max:255'],
            'device_model' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function toDto(): DeviceRegistrationDTO
    {
        return new DeviceRegistrationDTO(
            clientAppId: (int) $this->validated('client_app_id'),
            fcmToken: $this->validated('fcm_token'),
            osType: OsType::from($this->validated('os_type')),
            appVersion: $this->validated('app_version'),
            deviceId: $this->validated('device_id'),
            deviceModel: $this->validated('device_model'),
        );
    }
}