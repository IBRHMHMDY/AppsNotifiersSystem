<?php

use App\Http\Controllers\Api\CheckUpdateController;
use App\Http\Controllers\Api\SyncDeviceController;
use App\Http\Middleware\VerifyAppKey;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->middleware([VerifyAppKey::class])->group(function () {
    
    // نقطة مزامنة أجهزة Flutter (يتم استدعاؤها في main() أو عند تحديث FCM Token)
    Route::post('/devices/sync', SyncDeviceController::class);
    
    // نقطة التحقق من التحديثات
    Route::get('/updates/check', CheckUpdateController::class);
    
});