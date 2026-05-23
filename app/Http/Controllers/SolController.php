<?php

namespace App\Http\Controllers;

use App\Models\Run;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

/**
 * SolController — handles player-triggered Sol (tick) advancement.
 *
 * In singleplayer mode the player decides when to advance time.
 * Each call to next() increments current_tick on the active run and
 * immediately processes that tick via the game:tick Artisan command.
 *
 * Atomicity note: increment() issues a single SQL UPDATE ... SET current_tick = current_tick + 1
 * which is safe for SQLite singleplayer. No job queue or event system needed here.
 */
class SolController extends Controller
{
    public function next(Request $request): RedirectResponse
    {
        $run = Run::where('user_id', auth()->id())
            ->where('status', 'active')
            ->firstOrFail();

        // Increment first (atomic), then process so the tick command sees the updated value.
        $run->increment('current_tick');
        $run->refresh();

        Artisan::call('game:tick', ['--run' => $run->id]);

        return redirect()->back()->with('sol_advanced', $run->current_tick);
    }
}
