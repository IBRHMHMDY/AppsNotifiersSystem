<?php
// app/Http/Requests/Api/V1/CheckUpdateRequest.php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use App\DTOs\CheckUpdateDTO;
use App\Enums\OsType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CheckUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'app_version' => ['required', 'string', 'max:50'],
            'os_type' => ['required', Rule::enum(OsType::class)],
            'device_id' => ['required', 'string', 'max:255'],
        ];
    }

    public function toDto(): CheckUpdateDTO
    {
        return new CheckUpdateDTO(
            clientAppId: $this->attributes->get('client_app')->id,
            appVersion: $this->validated('app_version'),
            osType: OsType::from($this->validated('os_type')),
            deviceId: $this->validated('device_id'),
        );
    }
}