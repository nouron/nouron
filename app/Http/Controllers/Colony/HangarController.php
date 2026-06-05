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

        $slots        = $this->hangarService->getHangarSlots($colony->id);
        $pendingShips = $this->hangarService->getPendingShips($colony->id);

        $shipTypes = DB::table('ships')
            ->whereIn('id', [37, 47, 85])
            ->where('is_active', 1)
            ->get(['id', 'name']);

        $hasPilot = DB::table('advisors')
            ->where('colony_id', $colony->id)
            ->where('personell_id', 89)
            ->exists();

        // Ship costs for the request modal (keyed by ship DB id).
        $shipCosts = [
            85 => ['cost' => config('ships.drone.nexus_cost'),     'delivery_ticks' => config('ships.drone.nexus_delivery_ticks')],
            37 => ['cost' => config('ships.corvette.nexus_cost'),  'delivery_ticks' => config('ships.corvette.nexus_delivery_ticks')],
            47 => ['cost' => config('ships.freighter.nexus_cost'), 'delivery_ticks' => config('ships.freighter.nexus_delivery_ticks')],
        ];

        // Nexus-Kredit available if CC level >= threshold defined in game config.
        $ccLevel = (int) DB::table('colony_buildings')
            ->where('colony_id', $colony->id)
            ->where('building_id', 1) // CommandCenter building_id
            ->value('level');
        $canUseNexusCredit = $ccLevel >= (int) config('game.hangar.nexus_credit_min_cc_level', 2);

        // Konsul (trader advisor, personell_id = 92) active check.
        // TODO: replace with AdvisorService call once economy-AP pool is exposed via service.
        $konsulPersonellId = (int) config('advisors.trader.id', 92);
        $hasAktivierterKonsul = DB::table('advisors')
            ->where('colony_id', $colony->id)
            ->where('personell_id', $konsulPersonellId)
            ->exists();
        $verfuegbareVerhandlungsAP = 0; // TODO: read from economy AP pool once implemented

        // IDs of ship types that are already commissioned (docked/building/dispatched)
        // so the UI can mark them as unavailable for re-ordering.
        $commissionedShipIds = DB::table('colony_ships')
            ->where('colony_id', $colony->id)
            ->whereNotNull('hangar_instance_id')
            ->pluck('ship_id')
            ->unique()
            ->values()
            ->all();

        return view('colony.hangar', compact(
            'slots',
            'pendingShips',
            'shipTypes',
            'hasPilot',
            'shipCosts',
            'canUseNexusCredit',
            'hasAktivierterKonsul',
            'verfuegbareVerhandlungsAP',
            'commissionedShipIds',
        ));
    }

    /**
     * Request a ship from the Nexus (replaces the old buildShip action).
     * POST /colony/hangar/request
     */
    public function requestShip(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ship_id'          => 'required|integer|in:37,47,85',
            'use_nexus_credit' => 'boolean',
            'consul_ap_spent'  => 'integer|min:0|max:20',
        ]);

        $colony = $this->colonyService->getPrimeColony(Auth::id());

        try {
            $this->hangarService->requestShip(
                $colony->id,
                (int) $validated['ship_id'],
                (bool) ($validated['use_nexus_credit'] ?? false),
                (int) ($validated['consul_ap_spent'] ?? 0),
            );
        } catch (\RuntimeException $e) {
            return response()->json(['ok' => false, 'error' => $e->getMessage()], 422);
        }

        return response()->json([
            'ok'      => true,
            'slots'   => $this->hangarService->getHangarSlots($colony->id),
            'pending' => $this->hangarService->getPendingShips($colony->id),
        ]);
    }

    /**
     * Assign a pending ship to a hangar slot.
     * POST /colony/hangar/assign
     */
    public function assignToHangar(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ship_row_id' => 'required|integer|min:1',
            'instance_id' => 'required|integer|min:1',
        ]);

        $colony = $this->colonyService->getPrimeColony(Auth::id());

        try {
            $this->hangarService->assignToHangar(
                $colony->id,
                (int) $validated['ship_row_id'],
                (int) $validated['instance_id'],
            );
        } catch (\RuntimeException $e) {
            return response()->json(['ok' => false, 'error' => $e->getMessage()], 422);
        }

        return response()->json([
            'ok'      => true,
            'slots'   => $this->hangarService->getHangarSlots($colony->id),
            'pending' => $this->hangarService->getPendingShips($colony->id),
        ]);
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
