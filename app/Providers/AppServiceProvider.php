<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Broadcast;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Enable broadcasting routes with custom middleware for local development
        if (app()->environment('local')) {
            Broadcast::routes(['middleware' => ['web', \App\Http\Middleware\LocalBroadcastAuth::class]]);
        } else {
            Broadcast::routes();
        }
    }
}
