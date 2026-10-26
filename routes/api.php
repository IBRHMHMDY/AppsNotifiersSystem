<?php

// routes/api.php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\CheckUpdateController;
use App\Http\Controllers\Api\V1\SyncDeviceController;
use App\Http\Controllers\Api\V1\TrackNotificationController;
use App\Http\Middleware\VerifyAppKey;
use Illuminate\Routing\Middleware\ThrottleRequests;
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
            ->middleware(ThrottleRequests::using('device-sync'))
            ->name('devices.sync');

        // نقطة التحقق من التحديثات
        Route::get('/updates/check', CheckUpdateController::class)
            ->middleware(ThrottleRequests::using('update-check'))
            ->name('updates.check');

        // نقطة التتبع التلقائي لاستلام وفتح الإشعارات (DELIVERED, OPENED, CLICKED)
        Route::post('/notifications/track', TrackNotificationController::class)
            ->middleware(ThrottleRequests::using('notification-track'))
            ->name('notifications.track');
    });
