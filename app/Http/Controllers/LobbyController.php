<?php

namespace App\Http\Controllers;

use App\Models\Colony;
use App\Models\Run;
use App\Services\OnboardingService;
use App\Services\RunProgressService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LobbyController extends Controller
{
    public function __construct(
        private readonly RunProgressService $runProgressService,
        private readonly OnboardingService $onboardingService,
    ) {}

    public function index(): View|RedirectResponse
    {
        $userId = Auth::id();

        $runs = Run::where('user_id', $userId)
            ->with('colony')
            ->orderByDesc('created_at')
            ->get();

        $pending = $runs->filter(fn (Run $r) => $r->status === 'active' && $r->started_at === null);
        $active = $runs->filter(fn (Run $r) => $r->status === 'active' && $r->started_at !== null);
        $finished = $runs->filter(fn (Run $r) => in_array($r->status, ['completed', 'failed'], true));

        // When the most recent active run has ended, redirect straight to result screen.
        $latestActive = $runs->first(fn (Run $r) => $r->status === 'active' && $r->started_at !== null);
        if ($latestActive === null) {
            $latestEnded = $runs->first(fn (Run $r) => in_array($r->status, ['completed', 'failed'], true));
            if ($latestEnded !== null && $runs->filter(fn (Run $r) => $r->status === 'active')->isEmpty()) {
                return redirect()->route('run.result', $latestEnded->id);
            }
        }

        $allowMultiple = config('game.run.allow_multiple', false);

        // Feature 1: finished runs for the highscore table.
        //
        // The score is read from runs.score, frozen by endRun(). It used to be recomputed
        // here against the player's *current* credits, so a finished run's score changed
        // every time the player earned or spent money afterwards. A highscore is history,
        // not a live query.
        $finishedRunCollection = Run::where('user_id', $userId)
            ->whereIn('status', ['completed', 'failed'])
            ->with(['objectives'])
            ->orderByDesc('ended_at')
            ->take(10)
            ->get();

        $finishedRuns = $finishedRunCollection->map(function (Run $run) {
            return [
                'id' => $run->id,
                'status' => $run->status,
                'current_tick' => $run->current_tick,
                'tick_limit' => $run->getTickLimit(),
                'ended_at' => $run->ended_at,
                'completed_objectives' => $run->objectives->whereNotNull('completed_at')->count(),
                'total_objectives' => $run->objectives->count(),
                'score' => (int) ($run->score ?? 0),
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
     * Resets the existing colony to the canonical Sol-1 starting state, then
     * creates a fresh Run record. Guards against starting a new run when an
     * active run already exists.
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

        $this->onboardingService->resetColonyToSol1($userId, $colony->id);

        return redirect()->route('lobby')->with('success', __('run.new_run_started'));
    }

    public function abandon(Run $run): RedirectResponse
    {
        if ($run->user_id !== Auth::id()) {
            abort(403);
        }

        if ($run->status !== 'active') {
            return redirect()->route('lobby')->with('error', __('lobby.abandon_not_active'));
        }

        $run->update([
            'status' => 'failed',
            'ended_at' => now(),
        ]);

        return redirect()->route('lobby')->with('success', __('lobby.abandon_success'));
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
