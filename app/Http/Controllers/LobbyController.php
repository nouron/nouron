<?php

namespace App\Http\Controllers;

use App\Models\Colony;
use App\Models\Run;
use App\Services\OnboardingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LobbyController extends Controller
{
    public function __construct(
        private readonly OnboardingService $onboardingService,
    ) {}

    /**
     * Lobby data for the SvelteKit screen: the same runs as index(), as plain rows.
     * resultRunId replaces index()'s redirect: set when the latest run ended and none is active.
     */
    public function data(): JsonResponse
    {
        $userId = Auth::id();
        $runs = Run::where('user_id', $userId)->with('colony')->orderByDesc('created_at')->get();

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

        $row = fn (Run $r) => [
            'id' => $r->id,
            'status' => $r->status,
            'colony_name' => $r->colony->name ?? __('lobby.colony_unnamed'),
            'current_tick' => (int) $r->current_tick,
            'tick_limit' => (int) ($r->settings['tick_limit'] ?? 100),
            'supply_cap' => $r->settings['supply_cap_max'] ?? null,
            'bypass' => is_array($r->settings['bypass'] ?? null) ? $r->settings['bypass'] : [],
            'started_at' => $r->started_at?->format('d.m.Y'),
            'ended_at' => $r->ended_at?->format('d.m.Y'),
        ];
        // The latest run just ended and none is active: the frontend goes straight to its result.
        $latestEnded = $runs->first(fn (Run $r) => in_array($r->status, ['completed', 'failed'], true));
        $showResult = $latestEnded !== null && $runs->every(fn (Run $r) => $r->status !== 'active');

        return response()->json([
            'active' => $runs->filter(fn (Run $r) => $r->status === 'active' && $r->started_at !== null)->map($row)->values(),
            'pending' => $runs->filter(fn (Run $r) => $r->status === 'active' && $r->started_at === null)->map($row)->values(),
            'finished' => $runs->filter(fn (Run $r) => in_array($r->status, ['completed', 'failed'], true))->map($row)->values(),
            'finishedRuns' => $finishedRuns->map(fn (array $e) => ['ended_at' => $e['ended_at']?->format('d.m.Y')] + $e)->values(),
            'allowMultiple' => (bool) config('game.run.allow_multiple', false),
            'resultRunId' => $showResult ? $latestEnded->id : null,
        ]);
    }

    /**
     * Feature 2: Create a new run for the authenticated user.
     *
     * Resets the existing colony to the canonical Sol-1 starting state, then
     * creates a fresh Run record. Guards against starting a new run when an
     * active run already exists.
     */
    public function newRun(Request $request): JsonResponse
    {
        $userId = Auth::id();

        // Guard: block if there is already an active run.
        $activeRun = Run::where('user_id', $userId)->where('status', 'active')->first();
        if ($activeRun !== null) {
            return $this->answer(false, __('run.new_run_active_exists'));
        }

        $colony = Colony::where('user_id', $userId)->first();

        if ($colony === null) {
            // No colony exists — this should not happen for a registered player,
            // but fall back gracefully so the user gets a clear message.
            return $this->answer(false, __('run.new_run_no_colony'));
        }

        $this->onboardingService->resetColonyToSol1($userId, $colony->id);

        return $this->answer(true, __('run.new_run_started'));
    }

    public function abandon(Request $request, Run $run): JsonResponse
    {
        if ($run->user_id !== Auth::id()) {
            abort(403);
        }

        if ($run->status !== 'active') {
            return $this->answer(false, __('lobby.abandon_not_active'));
        }

        $run->update([
            'status' => 'failed',
            'ended_at' => now(),
        ]);

        return $this->answer(true, __('lobby.abandon_success'));
    }

    public function start(Request $request): JsonResponse
    {
        $run = Run::where('user_id', auth()->id())
            ->where('status', 'active')
            ->whereNull('started_at')
            ->firstOrFail();

        $run->update(['started_at' => now()]);

        return response()->json(['ok' => true]);
    }

    private function answer(bool $ok, string $message): JsonResponse
    {
        return response()->json(['ok' => $ok, 'message' => $message], $ok ? 200 : 422);
    }
}
