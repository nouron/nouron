<?php

namespace App\Http\Middleware;

use App\Models\Run;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guards in-run game screens: a run must be active AND started (started_at set).
 *
 * A freshly created run is active but started_at = null (pending) until the
 * player clicks "Mission starten" in the lobby. Without this guard a pending
 * run could open the colony view but show no Sol button (dead end), since the
 * run UI is gated on started_at. Pending / missing runs are sent to the lobby.
 */
class EnsureRunStarted
{
    public function handle(Request $request, Closure $next): Response
    {
        $run = Run::where('user_id', Auth::id())
            ->where('status', 'active')
            ->first();

        if ($run === null || $run->started_at === null) {
            $redirect = redirect()->route('lobby')->with('info', __('lobby.run_not_started'));

            // AJAX/JSON callers (in-run fetch actions) get a JSON pointer instead of HTML.
            if ($request->expectsJson()) {
                return response()->json([
                    'ok' => false,
                    'error' => 'run_not_started',
                    'redirect' => route('lobby'),
                ], 409);
            }

            return $redirect;
        }

        return $next($request);
    }
}
