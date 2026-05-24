<?php

namespace App\Http\Controllers;

use App\Models\Colony;
use App\Models\Run;
use App\Services\RunProgressService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class LobbyController extends Controller
{
    public function __construct(
        private readonly RunProgressService $runProgressService,
    ) {}

    public function index(): View|RedirectResponse
    {
        $userId = Auth::id();

        $runs = Run::where('user_id', $userId)
            ->with('colony')
            ->orderByDesc('created_at')
            ->get();

        $pending  = $runs->filter(fn(Run $r) => $r->status === 'active' && $r->started_at === null);
        $active   = $runs->filter(fn(Run $r) => $r->status === 'active' && $r->started_at !== null);
        $finished = $runs->filter(fn(Run $r) => in_array($r->status, ['completed', 'failed'], true));

        // When the most recent active run has ended, redirect straight to result screen.
        $latestActive = $runs->first(fn(Run $r) => $r->status === 'active' && $r->started_at !== null);
        if ($latestActive === null) {
            $latestEnded = $runs->first(fn(Run $r) => in_array($r->status, ['completed', 'failed'], true));
            if ($latestEnded !== null && $runs->filter(fn(Run $r) => $r->status === 'active')->isEmpty()) {
                return redirect()->route('run.result', $latestEnded->id);
            }
        }

        $allowMultiple = config('game.run.allow_multiple', false);

        // Feature 1: finished runs with pre-calculated scores for the highscore table.
        // Eager-load objectives so RunProgressService->calculateScore() avoids N+1 queries.
        $finishedRuns = Run::where('user_id', $userId)
            ->whereIn('status', ['completed', 'failed'])
            ->with('objectives')
            ->orderByDesc('ended_at')
            ->take(10)
            ->get()
            ->map(function (Run $run) {
                return [
                    'id'                  => $run->id,
                    'status'              => $run->status,
                    'current_tick'        => $run->current_tick,
                    'tick_limit'          => $run->getTickLimit(),
                    'ended_at'            => $run->ended_at,
                    'completed_objectives' => $run->objectives->whereNotNull('completed_at')->count(),
                    'total_objectives'    => $run->objectives->count(),
                    'score'               => $this->runProgressService->calculateScore($run),
                ];
            });

        return view('lobby.index', compact(
            'runs',
            'pending',
            'active',
            'finished',
            'finishedRuns',
            'allowMultiple',
        ));
    }

    /**
     * Feature 2: Create a new run for the authenticated user.
     *
     * Resets the existing colony to starting state, then creates a fresh Run record.
     * Guards against starting a new run when an active run already exists.
     */
    public function newRun(Request $request): RedirectResponse
    {
        $userId = Auth::id();

        // Guard: block if there is already an active run.
        $activeRun = Run::where('user_id', $userId)->where('status', 'active')->first();
        if ($activeRun !== null) {
            return redirect()->route('lobby')->with('error', __('run.new_run_active_exists'));
        }

        $colony = Colony::where('user_id', $userId)->first();

        if ($colony === null) {
            // No colony exists — this should not happen for a registered player,
            // but fall back gracefully so the user gets a clear message.
            return redirect()->route('lobby')->with('error', __('run.new_run_no_colony'));
        }

        DB::transaction(function () use ($userId, $colony) {
            $colonyId = $colony->id;

            // Reset colony-level resources to starting values.
            DB::table('colony_resources')->where('colony_id', $colonyId)->delete();
            DB::table('colony_resources')->insert([
                ['resource_id' => 3,  'colony_id' => $colonyId, 'amount' => 200], // regolith
                ['resource_id' => 4,  'colony_id' => $colonyId, 'amount' => 0],   // werkstoffe
                ['resource_id' => 5,  'colony_id' => $colonyId, 'amount' => 0],   // organika
                ['resource_id' => 12, 'colony_id' => $colonyId, 'amount' => 0],   // moral/trust
            ]);

            // Reset user-level resources (credits + supply).
            DB::table('user_resources')->updateOrInsert(
                ['user_id' => $userId],
                ['credits' => 3000, 'supply' => 15],
            );

            // Remove all existing buildings on the colony.
            DB::table('colony_buildings')->where('colony_id', $colonyId)->delete();

            // Seed starting buildings: CommandCenter (25) and Harvester (27) at level 1.
            DB::table('colony_buildings')->insert([
                [
                    'colony_id'     => $colonyId,
                    'building_id'   => config('buildings.commandCenter.id', 25),
                    'level'         => 1,
                    'status_points' => 20,
                    'ap_spend'      => 0,
                ],
                [
                    'colony_id'     => $colonyId,
                    'building_id'   => config('buildings.harvester.id', 27),
                    'level'         => 1,
                    'status_points' => 20,
                    'ap_spend'      => 0,
                ],
            ]);

            // Release all advisors assigned to this colony.
            DB::table('advisors')->where('colony_id', $colonyId)->update(['colony_id' => null]);

            // Reset all colony tiles to unexplored state.
            DB::table('colony_tiles')
                ->where('colony_id', $colonyId)
                ->update(['is_explored' => false]);

            // Create the new run record.
            Run::create([
                'user_id'      => $userId,
                'colony_id'    => $colonyId,
                'current_tick' => 0,
                'status'       => 'active',
                'started_at'   => null, // set when player clicks "Mission starten" in lobby
                'phase'        => 1,
                'nexus_debt'   => 3000,
                'settings'     => [
                    'tick_limit'     => config('game.run.tick_limit'),
                    'bypass'         => config('game.bypass'),
                    'supply_cap_max' => config('game.supply.cap_max'),
                    'max_players'    => config('game.run.max_players'),
                ],
            ]);
        });

        return redirect()->route('lobby')->with('success', __('run.new_run_started'));
    }

    public function start(Request $request): RedirectResponse
    {
        $run = Run::where('user_id', auth()->id())
            ->where('status', 'active')
            ->whereNull('started_at')
            ->firstOrFail();

        $run->update(['started_at' => now()]);

        return redirect()->route('colony.view');
    }
}
