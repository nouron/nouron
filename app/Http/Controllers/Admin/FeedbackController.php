<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Feedback;
use App\Models\Run;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * R15: admin overview of tester feedback, newest first.
 */
class FeedbackController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()->isAdmin(), 403);

        $feedback = Feedback::with('user')->latest()->latest('id')->paginate(50);

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

    /** Debug bar data for admins: active run, bypass flags, environment, key config values. */
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
                // Sols are ended by hand (SolController::next); no real-time tick length applies.
                'Sol' => [
                    'Sol-Limit' => config('game.run.tick_limit'),
                    'AP-Basis' => config('game.ap.base'),
                    'AP-Deckel Gebäude' => config('game.build.ap_cap_per_sol.base'),
                    'AP-Deckel Kenntnis' => config('game.knowledge_ap_cap_per_sol.base'),
                ],
                'Kolonisten' => [
                    'Maximum' => config('game.supply.cap_max'),
                    'Kommandozentrale' => config('buildings.commandCenter.supply_cap'),
                    'je Wohnhabitat' => config('buildings.housingComplex.supply_cap'),
                ],
                'Credits' => [
                    'nexus_subsidy' => config('game.credits.nexus_subsidy').' Cr',
                    'relay_bonus_per_uplink_level' => config('game.credits.relay_bonus_per_uplink_level').' Cr',
                ],
                'Vertrauen' => config('game.trust.events'),
            ],
        ]);
    }
}
