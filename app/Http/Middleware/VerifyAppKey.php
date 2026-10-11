<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\ClientApp;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyAppKey
{
    /**
     * التحقق من وجود مفتاح التطبيق وصلاحيته.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $appKey = $request->header('X-App-Key');

        if (! $appKey) {
            return response()->json([
                'success' => false,
                'message' => 'Missing X-App-Key header.'
            ], 401);
        }

        // جلب التطبيق والتأكد من أنه مفعل
        $clientApp = ClientApp::where('app_key', $appKey)
            ->where('is_active', true)
            ->first();

        if (! $clientApp) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or inactive App Key.'
            ], 401);
        }

        // حقن التطبيق في الطلب (Request) لاستخدامه لاحقاً في الـ Controllers دون الحاجة للاستعلام عنه مجدداً
        $request->attributes->set('clientApp', $clientApp);

        return $next($request);
    }
}