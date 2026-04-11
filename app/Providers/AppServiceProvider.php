<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Services\RoomRoutingEngine;
use Illuminate\Support\Facades\URL;
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(RoomRoutingEngine::class, function () {
            return new RoomRoutingEngine();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (config('app.env') === 'production') {
            URL::forceScheme('https');
        }
        Gate::before(function ($user, $ability) {
            if ($user->hasRole('admin')) {
                return true; // Admin bypasses all permission checks
            }
        });
    }
}
