<?php

namespace App\Http\Controllers;

use App\Models\Run;
use App\Services\Techtree\PersonellService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

/**
 * SolController — handles player-triggered Sol (tick) advancement.
 */
class SolController extends Controller
{
    public function __construct(
        private readonly PersonellService $personellService,
    ) {}

    public function next(Request $request): RedirectResponse
    {
        $run = Run::where('user_id', auth()->id())
            ->where('status', 'active')
            ->firstOrFail();

        $run->increment('current_tick');
        $run->refresh();

        Artisan::call('game:tick', ['--run' => $run->id]);

        return redirect()->back()->with('sol_advanced', $run->current_tick);
    }

    /**
     * Return remaining unspent AP for the current player's colony.
     * Used by the Sol-button JS component to decide whether to show a confirm dialog.
     */
    public function remainingAp(): JsonResponse
    {
        $colonyId = session('activeIds.colonyId', 1);

        $construction = $this->personellService->getConstructionPoints($colonyId);
        $research     = $this->personellService->getAvailableActionPoints('research', $colonyId);
        $navigation   = $this->personellService->getAvailableActionPoints('navigation', $colonyId);

        return response()->json([
            'construction' => $construction,
            'research'     => $research,
            'navigation'   => $navigation,
            'total'        => $construction + $research + $navigation,
        ]);
    }
}
