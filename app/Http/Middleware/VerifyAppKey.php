<?php

// app/Http/Middleware/VerifyAppKey.php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Exceptions\InvalidAppKeyException;
use App\Models\ClientApp;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

final readonly class VerifyAppKey
{
    /**
     * التحقق من وجود مفتاح التطبيق وصلاحيته مع استخدام الكاش لتقليل الضغط على قاعدة البيانات.
     *
     * @throws InvalidAppKeyException
     */
    public function handle(Request $request, Closure $next): Response
    {
        $appKey = trim((string) $request->header('X-App-Key', ''));

        if ($appKey === '') {
            throw InvalidAppKeyException::missing();
        }

        // جلب التطبيق من الكاش (أو قاعدة البيانات عند عدم وجوده) لمدة ساعة
        /** @var ClientApp|null $clientApp */
        $clientApp = Cache::remember(
            ClientApp::getCacheKey($appKey),
            now()->addHour(),
            static fn (): ?ClientApp => ClientApp::query()
                ->where('app_key', $appKey)
                ->first()
        );

        if (! $clientApp || ! $clientApp->is_active) {
            throw InvalidAppKeyException::invalidOrInactive();
        }

        // حقن التطبيق في الطلب (Request) لاستخدامه في الـ FormRequests والـ Controllers
        $request->attributes->set('clientApp', $clientApp);

        return $next($request);
    }
}
