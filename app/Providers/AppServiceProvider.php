<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\PushNotificationServiceInterface;
use App\Models\User;
use App\Services\FirebasePushService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    /**
     * ربط العقود (Interfaces) بالخدمات الفعلية في حاوية النظام.
     *
     * @var array<class-string, class-string>
     */
    public array $bindings = [
        PushNotificationServiceInterface::class => FirebasePushService::class,
    ];

    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // تفعيل الوضع الصارم للنماذج في غير بيئة الإنتاج لاكتشاف N+1 والحقول غير المحمية
        Model::shouldBeStrict(! $this->app->environment('production'));
        $this->configureRateLimiting();
        Livewire::setScriptRoute(function ($handle) {
            return Route::get('/vendor/livewire/livewire.min.js', $handle);
        });
    }

    /**
     * ضبط محددات معدل الطلبات (Rate Limiters) لحماية واجهات الـ API.
     */
    private function configureRateLimiting(): void
    {
        RateLimiter::for('device-sync', static function (Request $request): Limit {
            $identifier = $request->ip().'|'.$request->header('X-App-Key', 'anonymous');

            return Limit::perMinute(15)->by($identifier);
        });

        RateLimiter::for('update-check', static function (Request $request): Limit {
            $identifier = $request->ip().'|'.$request->header('X-App-Key', 'anonymous');

            return Limit::perMinute(60)->by($identifier);
        });

        Gate::define('viewPulse', static function (User $user): bool {
            return true; // أو $user->hasRole('super_admin') عند تفعيل الأدوار
        });
    }
}
