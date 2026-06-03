<?php

namespace App\Http\Controllers\Colony;

use App\Http\Controllers\BaseController;
use App\Services\ColonyService;
use App\Services\HangarService;
use App\Services\TickService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class HangarController extends BaseController
{
    public function __construct(
        TickService $tick,
        private readonly ColonyService $colonyService,
        private readonly HangarService $hangarService,
    ) {
        parent::__construct($tick);
    }

    public function index(): View
    {
        $colony = $this->colonyService->getPrimeColony(Auth::id());

        $slots = $this->hangarService->getHangarSlots($colony->id);

        $shipTypes = DB::table('ships')
            ->whereIn('id', [37, 47, 85])
            ->where('is_active', 1)
            ->get(['id', 'name']);

        $hasPilot = DB::table('advisors')
            ->where('colony_id', $colony->id)
            ->where('personell_id', 89)
            ->exists();

        return view('colony.hangar', compact('slots', 'shipTypes', 'hasPilot'));
    }

    public function build(Request $request, int $instanceId): JsonResponse
    {
        $validated = $request->validate([
            'ship_id' => 'required|integer|in:37,47,85',
        ]);

        $colony = $this->colonyService->getPrimeColony(Auth::id());

        try {
            $this->hangarService->buildShip($colony->id, $instanceId, $validated['ship_id']);
        } catch (\RuntimeException $e) {
            return response()->json(['ok' => false, 'error' => $e->getMessage()], 422);
        }

        return response()->json(['ok' => true, 'slot' => $this->fetchSlot($colony->id, $instanceId)]);
    }

    public function dispatch(Request $request, int $instanceId): JsonResponse
    {
        $validated = $request->validate([
            'destination'  => 'required|string|max:80',
            'sol_distance' => 'required|integer|min:1|max:999',
        ]);

        $colony = $this->colonyService->getPrimeColony(Auth::id());

        try {
            $this->hangarService->dispatchShip(
                $colony->id,
                $instanceId,
                $validated['destination'],
                $validated['sol_distance'],
            );
        } catch (\RuntimeException $e) {
            return response()->json(['ok' => false, 'error' => $e->getMessage()], 422);
        }

        return response()->json(['ok' => true, 'slot' => $this->fetchSlot($colony->id, $instanceId)]);
    }

    public function recall(Request $request, int $instanceId): JsonResponse
    {
        $colony = $this->colonyService->getPrimeColony(Auth::id());

        try {
            $this->hangarService->recallShip($colony->id, $instanceId);
        } catch (\RuntimeException $e) {
            return response()->json(['ok' => false, 'error' => $e->getMessage()], 422);
        }

        return response()->json(['ok' => true, 'slot' => $this->fetchSlot($colony->id, $instanceId)]);
    }

    public function repair(Request $request, int $instanceId): JsonResponse
    {
        $validated = $request->validate([
            'ap_spent' => 'required|integer|min:1|max:10',
        ]);

        $colony = $this->colonyService->getPrimeColony(Auth::id());

        try {
            $this->hangarService->repairShip($colony->id, $instanceId, $validated['ap_spent']);
        } catch (\RuntimeException $e) {
            return response()->json(['ok' => false, 'error' => $e->getMessage()], 422);
        }

        return response()->json(['ok' => true, 'slot' => $this->fetchSlot($colony->id, $instanceId)]);
    }

    private function fetchSlot(int $colonyId, int $instanceId): ?array
    {
        $slots = $this->hangarService->getHangarSlots($colonyId);
        foreach ($slots as $slot) {
            if ($slot['instance_id'] === $instanceId) {
                return $slot;
            }
        }
        return null;
    }
}
