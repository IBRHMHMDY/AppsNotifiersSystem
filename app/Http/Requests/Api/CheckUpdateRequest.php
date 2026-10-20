<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use App\DTOs\CheckUpdateDTO;
use App\Enums\OsType;
use App\Models\ClientApp;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CheckUpdateRequest extends FormRequest
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
            'current_version' => ['nullable', 'string', 'max:50'],
            'os_type' => ['nullable', Rule::enum(OsType::class)],
            'device_id' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function clientApp(): ClientApp
    {
        /** @var ClientApp $clientApp */
        $clientApp = $this->attributes->get('clientApp');

        return $clientApp;
    }

    public function toDto(): CheckUpdateDTO
    {
        /** @var array{current_version?: string|null, os_type?: string|null, device_id?: string|null} $validated */
        $validated = $this->validated();

        $deviceIdentifier = (string) (
            $validated['device_id']
            ?? $this->header('X-Device-Id')
            ?? $this->ip()
            ?? 'default-device'
        );

        return new CheckUpdateDTO(
            clientAppId: (int) $this->clientApp()->id,
            deviceIdentifier: $deviceIdentifier,
            currentVersion: $validated['current_version'] ?? null,
            osType: isset($validated['os_type']) ? OsType::from($validated['os_type']) : null,
        );
    }
}
