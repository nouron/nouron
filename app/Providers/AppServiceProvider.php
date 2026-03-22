<?php

namespace App\Providers;

use App\Services\TickService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // TickService as a singleton — tick count must stay consistent per request
        $this->app->singleton(TickService::class, function () {
            return new TickService();
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
