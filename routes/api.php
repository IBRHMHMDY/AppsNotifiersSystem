<?php

declare(strict_types=1);

use App\Http\Controllers\Api\CheckUpdateController;
use App\Http\Controllers\Api\SyncDeviceController;
use App\Http\Middleware\VerifyAppKey;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes (v1)
|--------------------------------------------------------------------------
*/

Route::prefix('v1')
    ->as('api.v1.')
    ->middleware([VerifyAppKey::class])
    ->group(function (): void {
        // نقطة مزامنة أجهزة Flutter (يتم استدعاؤها في main() أو عند تحديث FCM Token)
        Route::post('/devices/sync', SyncDeviceController::class)
            ->middleware('throttle:device-sync')
            ->name('devices.sync');

        // نقطة التحقق من التحديثات
        Route::get('/updates/check', CheckUpdateController::class)
            ->middleware('throttle:update-check')
            ->name('updates.check');
    });
