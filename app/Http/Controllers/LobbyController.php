<?php

namespace App\Http\Controllers;

use App\Models\Run;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LobbyController extends Controller
{
    public function index(): View
    {
        $runs = Run::where('user_id', auth()->id())
            ->with('colony')
            ->orderByDesc('created_at')
            ->get();

        $pending  = $runs->filter(fn(Run $r) => $r->status === 'active' && $r->started_at === null);
        $active   = $runs->filter(fn(Run $r) => $r->status === 'active' && $r->started_at !== null);
        $finished = $runs->filter(fn(Run $r) => in_array($r->status, ['completed', 'failed'], true));

        $allowMultiple = config('game.run.allow_multiple', false);

        return view('lobby.index', compact('runs', 'pending', 'active', 'finished', 'allowMultiple'));
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
