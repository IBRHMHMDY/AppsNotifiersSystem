<?php
// app/Http/Middleware/AllowExpiredAppKey.php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\ClientApp;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class AllowExpiredAppKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $appKey = $request->header('X-App-Key') ?? $request->bearerToken();

        if (! $appKey) {
            return $this->forceUpdateResponse();
        }

        // نبحث عن التطبيق بناءً على المفتاح، حتى لو كان is_active = false
        $clientApp = Cache::remember("client_app_any_{$appKey}", now()->addHours(24), function () use ($appKey) {
            $app = ClientApp::query()->where('app_key', $appKey)->first();
            return $app?->toArray();
        });

        // إذا تم تغيير المفتاح بالكامل في قاعدة البيانات ولم يعد له وجود
        if (! $clientApp) {
            return $this->forceUpdateResponse();
        }

        $request->merge(['client_app_id' => $clientApp['id']]);

        return $next($request);
    }

    /**
     * استجابة وهمية لفرض التحديث الإجباري لإنقاذ التطبيقات الميتة (ذات المفاتيح المحذوفة)
     */
    private function forceUpdateResponse(): Response
    {
        return response()->json([
            'success' => true,
            'message' => 'App key expired or missing. Force update required.',
            'data' => [
                'version' => '999.9.9',
                'store_url' => null,
                'is_force_update' => true,
                'release_notes' => 'عذراً، تم إيقاف دعم هذه النسخة لدواعي أمنية. يرجى تحديث التطبيق للمتابعة.',
                'update_available' => true,
                'has_newer_version' => true,
                'is_blocked_version' => true,
                'force_reason' => 'invalid_app_key',
                'rollout_percentage' => 100,
                'is_under_maintenance' => false,
                'maintenance_message' => null,
            ]
        ], 200); 
    }
}