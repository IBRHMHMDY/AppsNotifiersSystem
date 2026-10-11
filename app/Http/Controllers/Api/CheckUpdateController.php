<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CheckUpdateController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        // جلب التطبيق المحقون من الـ Middleware
        $clientApp = $request->attributes->get('clientApp');
        
        // جلب أحدث تحديث تم تسجيله لهذا التطبيق
        $latestUpdate = $clientApp->updates()->latest('created_at')->first();

        if (! $latestUpdate) {
            return response()->json([
                'success' => true,
                'message' => 'No updates available.',
                'data' => null
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Latest update fetched successfully.',
            'data' => [
                'version' => $latestUpdate->version,
                'store_url' => $latestUpdate->store_url,
                'is_force_update' => $latestUpdate->is_force_update,
                'release_notes' => $latestUpdate->release_notes,
            ]
        ]);
    }
}