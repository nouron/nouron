<?php

namespace App\Http\Controllers;

use App\Models\Run;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LobbyController extends Controller
{
    public function index(): View|RedirectResponse
    {
        $runs = Run::where('user_id', auth()->id())
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

        return view('lobby.index', compact('runs', 'pending', 'active', 'finished', 'allowMultiple'));
    }

    public function newRun(Request $request): RedirectResponse
    {
        return redirect()->route('lobby')->with('success', __('run.new_run_preparing'));
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
