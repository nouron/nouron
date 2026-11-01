<?php

namespace App\Providers;

use App\Models\Run;
use App\Services\AdvisorService;
use App\Services\BarService;
use App\Services\ColonyService;
use App\Services\EventService;
use App\Services\MerchantService;
use App\Services\ProjectBonusService;
use App\Services\ResourcesService;
use App\Services\ShellStateService;
use App\Services\Techtree\BuildingService;
use App\Services\Techtree\ResearchService;
use App\Services\Techtree\TechtreeColonyService;
use App\Services\TickService;
use App\Services\TradeAdvantageService;
use App\Services\TrustService;
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
                return new TickService; // time-based fallback for Artisan / tests
            }

            $userId = auth()->id();
            $run = $userId
                ? Run::where('user_id', $userId)->where('status', 'active')->first()
                : null;

            return new TickService($run?->current_tick);
        });

        $this->app->bind(ColonyService::class, ColonyService::class);
        $this->app->bind(EventService::class, EventService::class);
        $this->app->bind(MerchantService::class, fn ($app) => new MerchantService(
            $app->make(AdvisorService::class),
            $app->make(BarService::class),
            $app->make(ResourcesService::class),
            $app->make(TradeAdvantageService::class),
        ));
        $this->app->bind(ResourcesService::class, ResourcesService::class);
        $this->app->bind(TrustService::class, fn ($app) => new TrustService(
            $app->make(TickService::class),
        ));

        // Advisor + techtree services
        $this->app->bind(AdvisorService::class, fn ($app) => new AdvisorService(
            $app->make(TickService::class),
            $app->make(TrustService::class),
            $app->make(ResourcesService::class),
        ));
        $this->app->bind(BuildingService::class, fn ($app) => new BuildingService(
            $app->make(TickService::class),
            $app->make(ResourcesService::class),
            $app->make(AdvisorService::class),
        ));
        $this->app->bind(ResearchService::class, fn ($app) => new ResearchService(
            $app->make(TickService::class),
            $app->make(ResourcesService::class),
            $app->make(ProjectBonusService::class),
            $app->make(AdvisorService::class),
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
                if (! $colonyId || ! DB::table('glx_colonies')->where('id', $colonyId)->where('user_id', Auth::id())->exists()) {
                    $colonyId = (int) DB::table('glx_colonies')->where('user_id', Auth::id())->where('is_primary', 1)->value('id');
                    if ($colonyId) {
                        session(['activeIds.colonyId' => $colonyId]);
                    }
                }
                // Header data (resource bar, nav gates, hint, notices) — shared with GET /api/v1/shell.
                $view->with(app(ShellStateService::class)->forUser((int) Auth::id(), $colonyId ?: null));
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
                .'(GAME_BYPASS_AP, GAME_BYPASS_RESOURCES, GAME_BYPASS_SUPPLY). '
                .'game.dev_mode will be removed in a future release.',
                E_USER_DEPRECATED
            );
            Log::warning('[DEPRECATED] game.dev_mode is set — expanding to all game.bypass.* flags. '
                .'Switch to GAME_BYPASS_AP / GAME_BYPASS_RESOURCES / GAME_BYPASS_SUPPLY.');
            config([
                'game.bypass.ap_checks' => true,
                'game.bypass.resource_costs' => true,
                'game.bypass.supply_checks' => true,
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
