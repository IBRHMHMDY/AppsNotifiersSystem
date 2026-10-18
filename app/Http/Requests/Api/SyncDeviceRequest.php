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
        // التأكد من أن الـ Middleware قام بحقن كائن التطبيق بنجاح
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
        ];
    }

    /**
     * إرجاع كائن التطبيق المحقون من الـ Middleware.
     */
    public function clientApp(): ClientApp
    {
        /** @var ClientApp $clientApp */
        $clientApp = $this->attributes->get('clientApp');

        return $clientApp;
    }

    /**
     * بناء كائن نقل البيانات مباشرة من الطلب المفحوص.
     */
    public function toDto(): DeviceRegistrationDTO
    {
        /** @var array{fcm_token: string, os_type: string, app_version: string} $validated */
        $validated = $this->validated();
        $clientApp = $this->clientApp();

        return new DeviceRegistrationDTO(
            appKey: (string) $clientApp->app_key,
            fcmToken: $validated['fcm_token'],
            osType: OsType::from($validated['os_type']),
            appVersion: $validated['app_version'],
            clientAppId: (int) $clientApp->id,
        );
    }
}