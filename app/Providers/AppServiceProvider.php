<?php

namespace App\Providers;

use App\Services\ColonyService;
use App\Services\EventService;
use App\Services\GalaxyService;
use App\Services\MessageService;
use App\Services\MoralService;
use App\Services\ResourcesService;
use App\Services\Techtree\BuildingService;
use App\Services\Techtree\PersonellService;
use App\Services\Techtree\ResearchService;
use App\Services\Techtree\ShipService;
use App\Services\Techtree\TechtreeColonyService;
use App\Services\TickService;
use App\Services\FleetService;
use App\Services\TradeGateway;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;
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
        $this->app->bind(MoralService::class, fn($app) => new MoralService(
            $app->make(TickService::class),
        ));
        $this->app->bind(TradeGateway::class, fn($app) => new TradeGateway(
            $app->make(ColonyService::class),
            $app->make(MoralService::class),
            $app->make(PersonellService::class),
        ));
        $this->app->bind(FleetService::class, FleetService::class);

        // Techtree services
        $this->app->bind(PersonellService::class, fn($app) => new PersonellService(
            $app->make(TickService::class),
            $app->make(MoralService::class),
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
        $this->bootBypassFlags();

        // Inject resource bar data into game layouts for authenticated users
        View::composer(['layouts.app', 'layouts.colony'], function ($view) {
            if (Auth::check()) {
                $colonyId = session('activeIds.colonyId', 1);
                $resourcesService = app(ResourcesService::class);
                $possessions  = $resourcesService->getPossessionsByColonyId($colonyId);
                $resourceTypes = $resourcesService->getResources()->keyBy('id');
                foreach ($possessions as $resId => $poss) {
                    if (isset($resourceTypes[$resId])) {
                        $possessions[$resId] = array_merge($poss, $resourceTypes[$resId]->toArray());
                    }
                }
                $view->with('resourceBarPossessions', $possessions ?? []);
            }
        });
    }

    /**
     * Handle game.bypass flags and the legacy game.dev_mode shortcut.
     *
     * - If game.dev_mode is true, expand it into all bypass flags (with a deprecation warning).
     * - In production, throw if any bypass flag is active.
     */
    private function bootBypassFlags(): void
    {
        // Legacy dev_mode shortcut — expand to individual bypass flags and warn.
        if (config('game.dev_mode')) {
            trigger_error(
                'config(\'game.dev_mode\') is deprecated. Use individual game.bypass.* flags instead '
                . '(GAME_BYPASS_AP, GAME_BYPASS_RESOURCES, GAME_BYPASS_SUPPLY). '
                . 'game.dev_mode will be removed in a future release.',
                E_USER_DEPRECATED
            );
            Log::warning('[DEPRECATED] game.dev_mode is set — expanding to all game.bypass.* flags. '
                . 'Switch to GAME_BYPASS_AP / GAME_BYPASS_RESOURCES / GAME_BYPASS_SUPPLY.');
            config([
                'game.bypass.ap_checks'      => true,
                'game.bypass.resource_costs' => true,
                'game.bypass.supply_checks'  => true,
            ]);
        }

        // Production guard — bypass flags must never be active in production.
        if ($this->app->isProduction() && array_filter(config('game.bypass', []))) {
            $active = implode(', ', array_keys(array_filter(config('game.bypass', []))));
            throw new \RuntimeException(
                "Game bypass flags must not be active in production. Active: [{$active}]"
            );
        }
    }
}
