<?php
// app/Http/Middleware/VerifyAppKey.php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\ClientApp;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class VerifyAppKey
{
    public function handle(Request $request, Closure $next): Response
    {
        // استخراج المفتاح حسب طريقتك (Header أو غيره)
        $appKey = $request->header('X-App-Key') ?? $request->bearerToken();

        if (! $appKey) {
            return response()->json([
                'success' => false,
                'message' => 'App Key is missing.'
            ], 401);
        }

        // الحل الجذري: تخزين مصفوفة (Array) بدلاً من الكائن (Model)
        $clientApp = Cache::remember("client_app_{$appKey}", now()->addHours(24), function () use ($appKey) {
            $app = ClientApp::query()
                ->where('app_key', $appKey)
                ->where('is_active', true)
                ->first();
                
            return $app ? clone $app->toArray() : null; // إرجاع مصفوفة بسيطة يسهل تشفيرها
        });

        if (! $clientApp) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or inactive App Key.'
            ], 401);
        }

        // دمج الـ ID في الطلب لاستخدامه في Controllers لاحقاً
        $request->merge(['client_app_id' => $clientApp['id']]);

        return $next($request);
    }
}