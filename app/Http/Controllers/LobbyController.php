<?php

namespace App\Http\Controllers;

use App\Models\Run;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LobbyController extends Controller
{
    public function index(): mixed
    {
        $run = Run::where('user_id', auth()->id())
            ->where('status', 'active')
            ->with('colony')
            ->first();

        // Already started — send straight to the game.
        if ($run && $run->started_at !== null) {
            return redirect()->route('colony.view');
        }

        return view('lobby.index', compact('run'));
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
