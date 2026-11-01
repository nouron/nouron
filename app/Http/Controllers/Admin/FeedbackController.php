<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Feedback;
use App\Models\Run;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * R15: admin overview of tester feedback, newest first.
 */
class FeedbackController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        abort_unless($request->user()->isAdmin(), 403);

        $feedback = Feedback::with('user')->latest()->latest('id')->paginate(50);

        if ($request->is('api/*')) {
            return response()->json($feedback->through(fn (Feedback $entry) => [
                'id' => $entry->id,
                'created_at' => $entry->created_at->format('d.m.Y H:i'),
                'username' => $entry->user->username, // FK cascades: the user always exists
                'category' => __('feedback.category_'.$entry->category),
                'run_id' => $entry->run_id,
                'sol' => $entry->sol,
                'page' => $entry->page,
                'message' => $entry->message,
            ]));
        }

        return view('admin.feedback', ['feedback' => $feedback]);
    }

    /** Debug bar for admins (same content as partials/debug-bar.blade.php, minus the finale previews). */
    public function debug(Request $request): JsonResponse
    {
        abort_unless($request->user()->isAdmin(), 403);

        return response()->json([
            'run' => Run::where('user_id', $request->user()->user_id)->where('status', 'active')
                ->first(['id', 'current_tick', 'status']),
            'tick_limit' => config('game.run.tick_limit'),
            'bypass' => config('game.bypass'),
            'env' => app()->environment(),
            'config' => [
                'Tick' => ['length' => config('game.tick.length').'h'],
                'Supply' => [
                    'cap_max' => config('game.supply.cap_max'),
                    'cc_flat' => config('buildings.commandCenter.supply_cap'),
                    'per_housing' => config('buildings.housingComplex.supply_cap'),
                ],
                'Credits' => [
                    'nexus_subsidy' => config('game.credits.nexus_subsidy').' Cr',
                    'relay_bonus_per_uplink_level' => config('game.credits.relay_bonus_per_uplink_level').' Cr',
                ],
                'Trust Events' => config('game.trust.events'),
            ],
        ]);
    }
}
