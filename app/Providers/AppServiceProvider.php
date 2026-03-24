<?php

namespace App\Providers;

use App\Services\ColonyService;
use App\Services\EventService;
use App\Services\GalaxyService;
use App\Services\MessageService;
use App\Services\ResourcesService;
use App\Services\Techtree\BuildingService;
use App\Services\Techtree\PersonellService;
use App\Services\Techtree\ResearchService;
use App\Services\Techtree\ShipService;
use App\Services\Techtree\TechtreeColonyService;
use App\Services\TickService;
use App\Services\FleetService;
use App\Services\TradeGateway;
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

        $this->app->bind(ColonyService::class, ColonyService::class);
        $this->app->bind(EventService::class, EventService::class);
        $this->app->bind(GalaxyService::class, GalaxyService::class);
        $this->app->bind(MessageService::class, MessageService::class);
        $this->app->bind(ResourcesService::class, ResourcesService::class);
        $this->app->bind(TradeGateway::class, TradeGateway::class);
        $this->app->bind(FleetService::class, FleetService::class);

        // Techtree services — PersonellService has no dependency on itself
        $this->app->bind(PersonellService::class, fn($app) => new PersonellService(
            $app->make(TickService::class),
            $app->make(ResourcesService::class),
        ));
        $this->app->bind(BuildingService::class, fn($app) => new BuildingService(
            $app->make(TickService::class),
            $app->make(ResourcesService::class),
            $app->make(PersonellService::class),
        ));
        $this->app->bind(ResearchService::class, fn($app) => new ResearchService(
            $app->make(TickService::class),
            $app->make(ResourcesService::class),
            $app->make(PersonellService::class),
        ));
        $this->app->bind(ShipService::class, fn($app) => new ShipService(
            $app->make(TickService::class),
            $app->make(ResourcesService::class),
            $app->make(PersonellService::class),
        ));
        $this->app->bind(TechtreeColonyService::class, TechtreeColonyService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
