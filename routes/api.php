<?php

// routes/api.php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\CheckUpdateController;
use App\Http\Controllers\Api\V1\SyncDeviceController;
use App\Http\Controllers\Api\V1\TrackNotificationController;
use App\Http\Middleware\AllowExpiredAppKey;
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
    ->group(function (): void {

        // 1. نقطة التحقق من التحديثات (مستثناة من الحماية الصارمة لضمان وصول التحديث الإجباري)
        Route::middleware([AllowExpiredAppKey::class, ThrottleRequests::using('update-check')])->group(function (): void {
            Route::get('/updates/check', CheckUpdateController::class)
            ->name('updates.check');

        });

        // 2. المسارات المحمية بالمفتاح الصارم (يُرفض الطلب 401 إذا تغير المفتاح)
        Route::middleware([VerifyAppKey::class])->group(function (): void {
            // نقطة مزامنة أجهزة Flutter
            Route::post('/devices/sync', SyncDeviceController::class)
                ->middleware(ThrottleRequests::using('device-sync'))
                ->name('devices.sync');

            // نقطة التتبع التلقائي لاستلام وفتح الإشعارات
            Route::post('/notifications/track', TrackNotificationController::class)
                ->middleware(ThrottleRequests::using('notification-track'))
                ->name('notifications.track');
        });
    });
