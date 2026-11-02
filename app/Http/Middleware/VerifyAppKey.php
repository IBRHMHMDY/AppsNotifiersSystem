<?php
// app/Http/Middleware/VerifyAppKey.php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\ClientApp;
use App\Exceptions\InvalidAppKeyException;
use Symfony\Component\HttpFoundation\Response;

class VerifyAppKey
{
    /**
     * @throws InvalidAppKeyException
     */
    public function handle(Request $request, Closure $next): Response
    {
        $appKey = $request->header('X-App-Key');

        if (! $appKey) {
            throw new InvalidAppKeyException('App Key is missing in the request headers.');
        }

        $clientApp = ClientApp::where('app_key', $appKey)->where('is_active', true)->first();

        if (! $clientApp) {
            throw new InvalidAppKeyException('Invalid or inactive App Key.');
        }

        $request->merge(['client_app_id' => $clientApp->id]);

        return $next($request);
    }
}