<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use App\Enums\OsType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SyncDeviceRequest extends FormRequest
{
    public function authorize(): bool
    {
        // تم التحقق من الصلاحية بالفعل في الـ Middleware
        return true;
    }

    public function rules(): array
    {
        return [
            'fcm_token' => ['required', 'string', 'max:255'],
            'os_type' => ['required', Rule::enum(OsType::class)],
            'app_version' => ['required', 'string', 'max:50'],
        ];
    }
}