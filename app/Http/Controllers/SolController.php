<?php

namespace App\Http\Controllers;

use App\Models\Run;
use App\Services\EventService;
use App\Services\SolReportService;
use App\Services\Techtree\PersonellService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * SolController — handles player-triggered Sol (tick) advancement.
 */
class SolController extends Controller
{
    public function __construct(
        private readonly PersonellService $personellService,
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

        $this->eventService->createEvent([
            'user' => Auth::id(),
            'tick' => $run->current_tick,
            'event' => 'run.sol_advanced',
            'area' => 'run',
            'parameters' => json_encode(['colony_id' => $run->colony_id, 'sol' => $run->current_tick]),
        ]);

        $skipPref = (bool) (DB::table('user_preferences')
            ->where('user_id', Auth::id())
            ->value('sol_report_skip') ?? false);

        return response()->json($this->solReportService->buildReport($run, $before, $skipPref));
    }

    /**
     * Persist the player's "skip Sol-Report" preference (toggled from the report screen).
     */
    public function updateReportSkip(Request $request): JsonResponse
    {
        $data = $request->validate([
            'skip' => ['required', 'boolean'],
        ]);

        DB::table('user_preferences')->updateOrInsert(
            ['user_id' => Auth::id()],
            ['sol_report_skip' => $data['skip'], 'updated_at' => now()],
        );

        return response()->json(['skip' => (bool) $data['skip']]);
    }

    /**
     * Return remaining unspent AP for the current player's colony.
     * Used by the Sol-button JS component to decide whether to show a confirm dialog.
     */
    public function remainingAp(): JsonResponse
    {
        $colonyId = session('activeIds.colonyId', 1);

        $construction = $this->personellService->getConstructionPoints($colonyId);
        $research = $this->personellService->getAvailableActionPoints('research', $colonyId);
        $navigation = $this->personellService->getAvailableActionPoints('navigation', $colonyId);

        return response()->json([
            'construction' => $construction,
            'research' => $research,
            'navigation' => $navigation,
            'total' => $construction + $research + $navigation,
        ]);
    }
}
