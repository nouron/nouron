<?php

namespace App\Providers;

use App\Services\ColonyService;
use App\Services\GalaxyService;
use App\Services\MerchantService;
use App\Services\EventService;
use App\Services\TrustService;
use App\Services\ResourcesService;
use App\Services\Techtree\BuildingService;
use App\Services\Techtree\PersonellService;
use App\Services\Techtree\ResearchService;
use App\Services\Techtree\ShipService;
use App\Services\Techtree\TechtreeColonyService;
use App\Services\TickService;
use App\Services\FleetService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
        // TickService as a singleton — tick count must stay consistent per request.
        // For web requests the tick is sourced from the authenticated user's active Run
        // so that the Sol number shown in-game matches the run's current_tick.
        // Console commands (Artisan / scheduler) fall back to the time-based calculation.
        $this->app->singleton(TickService::class, function () {
            if (app()->runningInConsole()) {
                return new TickService(); // time-based fallback for Artisan / tests
            }

            $userId = auth()->id();
            $run    = $userId
                ? \App\Models\Run::where('user_id', $userId)->where('status', 'active')->first()
                : null;

            return new TickService($run?->current_tick);
        });

        $this->app->bind(ColonyService::class, ColonyService::class);
        $this->app->bind(EventService::class, EventService::class);
        $this->app->bind(MerchantService::class, fn($app) => new MerchantService(
            $app->make(PersonellService::class),
        ));
        $this->app->bind(GalaxyService::class, GalaxyService::class);

        $this->app->bind(ResourcesService::class, ResourcesService::class);
        $this->app->bind(TrustService::class, fn($app) => new TrustService(
            $app->make(TickService::class),
        ));
        $this->app->bind(FleetService::class, FleetService::class);

        // Techtree services
        $this->app->bind(PersonellService::class, fn($app) => new PersonellService(
            $app->make(TickService::class),
            $app->make(TrustService::class),
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
        // Force UTC for all PHP date/time operations to guarantee consistent tick
        // calculations regardless of the server's system timezone setting.
        // The tick system (config/game.php → tick) assumes UTC — never change this.
        date_default_timezone_set('UTC');

        $this->bootBypassFlags();

        // Inject resource bar data into game layouts for authenticated users
        View::composer(['layouts.app', 'layouts.colony'], function ($view) {
            if (Auth::check()) {
                $colonyId = (int) session('activeIds.colonyId', 0);
                // Validate the session colony belongs to the current user; heal stale sessions.
                if (!$colonyId || !DB::table('glx_colonies')->where('id', $colonyId)->where('user_id', Auth::id())->exists()) {
                    $colonyId = (int) DB::table('glx_colonies')->where('user_id', Auth::id())->where('is_primary', 1)->value('id');
                    if ($colonyId) {
                        session(['activeIds.colonyId' => $colonyId]);
                    }
                }
                $resourcesService = app(ResourcesService::class);
                $possessions = $colonyId ? $resourcesService->getPossessionsByColonyId($colonyId) : [];
                $resourceTypes = $resourcesService->getResources()->keyBy('id');
                foreach ($possessions as $resId => $poss) {
                    if (isset($resourceTypes[$resId])) {
                        $possessions[$resId] = array_merge($poss, $resourceTypes[$resId]->toArray());
                    }
                }
                $view->with('resourceBarPossessions', $possessions ?? []);

                // Inject Sol run-progress for the resource bar Sol chip
                $colony    = DB::table('glx_colonies')->where('user_id', Auth::id())->where('is_primary', 1)->first();
                $sinceTick = $colony ? (int) $colony->since_tick : null;
                $tickService = app(TickService::class);
                $globalTick  = $tickService->getTickCount();
                $solLimit    = (int) config('game.run.tick_limit', 100);
                $currentSol  = $sinceTick !== null ? min($solLimit, max(1, $globalTick - $sinceTick + 1)) : null;

                $view->with('currentSol', $currentSol);
                $view->with('solLimit', $solLimit);

                // Feature 3: Nexus debt from the active run — shown in the navbar.
                // nexus_debt_max = 12000 Cr (Nexus cancels the concession above this cap, GDD §15).
                // Only runs with started_at set count as "in-run" — pending runs do not show run UI.
                $activeRun = \App\Models\Run::where('user_id', Auth::id())
                    ->where('status', 'active')
                    ->whereNotNull('started_at')
                    ->first(['nexus_debt']);

                $view->with('nexusDebt', $activeRun?->nexus_debt);
                $view->with('nexusDebtMax', 12000);
                $view->with('inActiveRun', $activeRun !== null);

                // Cantina nav-link gating: grey out when bar not yet built
                $colonyIdForBar = session('activeIds.colonyId', 1);
                $barBuilt = DB::table('colony_buildings')
                    ->where('colony_id', $colonyIdForBar)
                    ->where('building_id', 52)
                    ->where('level', '>', 0)
                    ->exists();
                $view->with('barBuilt', $barBuilt);

                // Hangar nav-link gating: grey out when no hangar built
                $hangarBuilt = DB::table('colony_buildings')
                    ->where('colony_id', $colonyIdForBar)
                    ->where('building_id', 44)
                    ->where('level', '>', 0)
                    ->exists();
                $view->with('hangarBuilt', $hangarBuilt);

                // Nexus-Funk unread badge count for colony nav
                $unreadNexusCount = app(EventService::class)->countUnreadNexus(Auth::id());
                $view->with('unreadNexusCount', $unreadNexusCount);
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
