<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\TOTPService;
use App\Services\OAuthService;
use App\Services\PushNotificationService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(TOTPService::class, function ($app) {
            return new TOTPService();
        });

        $this->app->singleton(OAuthService::class, function ($app) {
            return new OAuthService();
        });

        $this->app->singleton(PushNotificationService::class, function ($app) {
            return new PushNotificationService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
