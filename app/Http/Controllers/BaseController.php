<?php

namespace App\Http\Controllers;

use App\Models\Run;
use App\Services\TickService;
use Illuminate\Support\Facades\Auth;

/**
 * BaseController — shared foundation for all Nouron game controllers.
 *
 * Provides:
 * - getCurrentUserId() — returns the authenticated user's ID or null
 * - getTick() — provides the current game tick
 * - currentSol() — the player-facing Sol number for the active run
 *
 * All game controllers should extend this class.
 */
abstract class BaseController extends Controller
{
    public function __construct(protected TickService $tick) {}

    /**
     * Returns the currently authenticated user's ID, or null if not logged in.
     */
    protected function getCurrentUserId(): ?int
    {
        return Auth::id();
    }

    /**
     * Returns the current game tick count.
     */
    protected function getTick(): int
    {
        return $this->tick->getTickCount();
    }

    /**
     * Returns the player-facing Sol number of the active run.
     *
     * The run's current_tick is the canonical clock: run start (current_tick = 0)
     * is "Sol 1", after one "Sol beenden" (current_tick = 1) it is "Sol 2".
     * Clamped to the configured run tick limit.
     */
    protected function currentSol(): int
    {
        $currentTick = (int) (Run::where('user_id', Auth::id())
            ->where('status', 'active')
            ->value('current_tick') ?? 0);

        $solLimit = (int) config('game.run.tick_limit', 100);

        return min($solLimit, max(1, $currentTick + 1));
    }
}
