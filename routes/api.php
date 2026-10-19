<?php

declare(strict_types=1);

use App\Http\Controllers\Api\CheckUpdateController;
use App\Http\Controllers\Api\SyncDeviceController;
use App\Http\Controllers\Api\TrackNotificationController;
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
        // نقطة مزامنة أجهزة Flutter
        Route::post('/devices/sync', SyncDeviceController::class)
            ->middleware('throttle:device-sync')
            ->name('devices.sync');

        // نقطة التحقق من التحديثات
        Route::get('/updates/check', CheckUpdateController::class)
            ->middleware('throttle:update-check')
            ->name('updates.check');

        // نقطة التتبع التلقائي لاستلام وفتح الإشعارات (DELIVERED, OPENED, CLICKED)
        Route::post('/notifications/track', TrackNotificationController::class)
            ->middleware('throttle:120,1')
            ->name('notifications.track');
    });