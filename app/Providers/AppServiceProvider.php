<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Disable auth completely by providing a dummy auth manager
        $this->app->singleton('auth', function () {
            return new class
            {
                public function __call($method, $parameters)
                {
                    return null;
                }
            };
        });

        // Disable auth.driver
        $this->app->singleton('auth.driver', function () {
            return new class
            {
                public function __call($method, $parameters)
                {
                    return null;
                }
            };
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // No broadcasting routes needed for public channels
    }
}
