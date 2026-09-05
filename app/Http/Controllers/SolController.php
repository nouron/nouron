<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesActiveColony;
use App\Models\Run;
use App\Services\AdvisorService;
use App\Services\EventService;
use App\Services\SolReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

/**
 * SolController — handles player-triggered Sol (tick) advancement.
 */
class SolController extends Controller
{
    use ResolvesActiveColony;

    public function __construct(
        private readonly AdvisorService $advisorService,
        private readonly EventService $eventService,
        private readonly SolReportService $solReportService,
    ) {}

    /**
     * Advance the active run by one Sol and return the Sol-Report as JSON.
     *
     * The colony state is snapshotted before the tick so the report can diff
     * the resulting "after" state and present what changed (see SolReportService).
     */
    public function next(Request $request): JsonResponse
    {
        $run = Run::where('user_id', auth()->id())
            ->where('status', 'active')
            ->firstOrFail();

        $before = $this->solReportService->snapshot(
            (int) $run->colony_id,
            (int) $run->user_id,
            (int) $run->phase,
        );

        $run->increment('current_tick');
        $run->refresh();

        Artisan::call('game:tick', ['--run' => $run->id]);
        $run->refresh();

        // Kommandozentrale-Dashboard "Netto-Sol-Bilanz" widget — the before/after
        // diff is only computable right now (colony state isn't snapshotted
        // anywhere persistent), so cache it for the dashboard to read later.
        Cache::put(
            "colony:{$run->colony_id}:last_sol_deltas",
            $this->solReportService->netDeltas((int) $run->colony_id, (int) $run->user_id, $before),
            now()->addDays(2),
        );

        $this->eventService->createEvent([
            'user' => Auth::id(),
            'tick' => $run->current_tick,
            'event' => 'run.sol_advanced',
            'area' => 'run',
            'parameters' => json_encode(['colony_id' => $run->colony_id, 'sol' => $run->current_tick]),
        ]);

        return response()->json($this->solReportService->buildReport($run, $before));
    }

    /**
     * Return remaining unspent AP for the current player's colony.
     * Used by the Sol-button JS component to decide whether to show a confirm dialog.
     */
    public function remainingAp(): JsonResponse
    {
        $colonyId = $this->resolveColonyId();

        $colonyAp = $this->advisorService->getAvailableActionPoints($colonyId);

        return response()->json([
            'colonyAp' => $colonyAp,
            'total' => $colonyAp,
        ]);
    }
}
