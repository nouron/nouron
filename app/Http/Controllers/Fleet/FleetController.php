<?php

namespace App\Http\Controllers\Fleet;

use App\Http\Controllers\BaseController;
use App\Models\Fleet;
use App\Models\FleetOrder;
use App\Services\ColonyService;
use App\Services\FleetService;
use App\Services\GalaxyService;
use App\Services\ResourcesService;
use App\Services\Techtree\PersonellService;
use App\Services\TickService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class FleetController extends BaseController
{
    public function __construct(
        private readonly FleetService     $fleetService,
        private readonly ColonyService    $colonyService,
        private readonly GalaxyService    $galaxyService,
        private readonly PersonellService $personellService,
        private readonly ResourcesService $resourcesService,
        private readonly TickService      $tickService,
    ) {}

    public function index(Request $request)
    {
        $userId   = $this->getCurrentUserId();
        $systemId = $request->route('sid');
        $objectId = $request->route('pid');
        $colonyId = $request->route('cid');
        $x        = $request->route('x');
        $y        = $request->route('y');

        if ($x && $y) {
            $ownFleets = $this->fleetService->getFleetsByCoords([$x, $y]);
        } elseif ($colonyId) {
            $ownFleets = $this->fleetService->getFleetsByEntityId('colony', $colonyId);
        } elseif ($objectId) {
            $ownFleets = $this->fleetService->getFleetsByEntityId('object', $objectId);
        } elseif ($systemId) {
            $ownFleets = $this->fleetService->getFleetsByEntityId('system', $systemId);
        } else {
            $ownFleets = $this->fleetService->getFleetsByUserId($userId);
        }

        $currentTick = $this->tickService->getTickCount();
        $fleetIds    = $ownFleets->where('user_id', $userId)->pluck('id')->all();
        $pendingOrders = FleetOrder::whereIn('fleet_id', $fleetIds)
            ->where('tick', '>=', $currentTick)
            ->where('was_processed', 0)
            ->orderBy('tick')
            ->get()
            ->groupBy('fleet_id');

        return view('fleet.index', compact('ownFleets', 'userId', 'pendingOrders', 'currentTick'));
    }

    public function config(int $id)
    {
        $userId = $this->getCurrentUserId();
        $fleet  = $this->fleetService->getFleet($id);

        if (!$fleet) {
            abort(404, 'Fleet not found.');
        }

        $colony = $this->colonyService->getColonyByCoords($fleet->getCoords());
        $fleetIsInColonyOrbit = $colony !== false;

        $resources  = $this->resourcesService->getResources()->keyBy('id');
        $ships      = \Illuminate\Support\Facades\DB::table('ships')->get()->keyBy('id');
        $personells = \Illuminate\Support\Facades\DB::table('personell')->get()->keyBy('id');
        $researches = \Illuminate\Support\Facades\DB::table('researches')->get()->keyBy('id');

        return view('fleet.config', compact('fleet', 'colony', 'fleetIsInColonyOrbit', 'resources', 'ships', 'personells', 'researches'));
    }

    // ── CRUD ─────────────────────────────────────────────────────────────────

    public function store(Request $request)
    {
        $userId = $this->getCurrentUserId();
        $request->validate([
            'fleet' => 'required|string|max:50',
        ]);

        try {
            $colony = $this->colonyService->getPrimeColony($userId);
        } catch (\RuntimeException) {
            return back()->withErrors(['fleet' => 'Keine Kolonie gefunden.']);
        }

        // Check that an available pilot advisor exists at this colony
        $availableCommander = DB::table('advisors')
            ->join('personell', 'personell.id', '=', 'advisors.personell_id')
            ->where('advisors.user_id', $userId)
            ->where('advisors.colony_id', $colony->id)
            ->whereNull('advisors.fleet_id')
            ->where('personell.can_command_fleet', 1)
            ->where(function ($q) {
                $tick = $this->tickService->getTickCount();
                $q->whereNull('advisors.unavailable_until_tick')
                  ->orWhere('advisors.unavailable_until_tick', '<=', $tick);
            })
            ->select('advisors.id')
            ->first();

        if (!$availableCommander) {
            return back()->withErrors(['fleet' => 'Kein verfügbarer Kommandant. Stelle zuerst einen Piloten ein.']);
        }

        [$cx, $cy, $cs] = $colony->getCoords();
        $fleet = Fleet::create([
            'fleet'   => $request->input('fleet'),
            'user_id' => $userId,
            'x'       => $cx,
            'y'       => $cy,
            'spot'    => $cs,
        ]);

        $this->personellService->assignToFleet($availableCommander->id, $fleet->id);

        return redirect()->route('fleet.index');
    }

    public function destroy(int $id)
    {
        $userId = $this->getCurrentUserId();
        $fleet  = Fleet::where('id', $id)->where('user_id', $userId)->first();

        if (!$fleet) {
            abort(403, 'Zugriff verweigert.');
        }

        DB::transaction(function () use ($fleet, $userId) {
            // Return commander to colony
            $commander = DB::table('advisors')
                ->where('fleet_id', $fleet->id)
                ->where('is_commander', 1)
                ->first();

            if ($commander) {
                $colony = $this->colonyService->getPrimeColony($userId);
                if ($colony) {
                    DB::table('advisors')->where('id', $commander->id)->update([
                        'fleet_id'  => null,
                        'colony_id' => $colony->id,
                        'is_commander' => 0,
                    ]);
                }
            }

            // Cascade delete fleet data
            DB::table('fleet_orders')->where('fleet_id', $fleet->id)->delete();
            DB::table('fleet_ships')->where('fleet_id', $fleet->id)->delete();
            DB::table('fleet_resources')->where('fleet_id', $fleet->id)->delete();
            DB::table('fleet_personell')->where('fleet_id', $fleet->id)->delete();
            DB::table('fleet_researches')->where('fleet_id', $fleet->id)->delete();
            $fleet->delete();
        });

        return redirect()->route('fleet.index');
    }

    // ── Orders ────────────────────────────────────────────────────────────────

    public function storeOrder(Request $request, int $id)
    {
        $userId = $this->getCurrentUserId();
        $fleet  = Fleet::where('id', $id)->where('user_id', $userId)->first();

        if (!$fleet) {
            abort(403, 'Zugriff verweigert.');
        }

        $order = $request->validate(['order' => 'required|in:move,trade,attack,hold,convoy,defend,join'])['order'];

        try {
            if ($order === 'move') {
                $request->validate([
                    'destination_x' => 'required|integer',
                    'destination_y' => 'required|integer',
                ]);
                $destX = (int) $request->input('destination_x');
                $destY = (int) $request->input('destination_y');

                // Phase 2: only within-system movement allowed
                $fleetSystem = $this->galaxyService->getSystemByObjectCoords([$fleet->x, $fleet->y]);
                $destSystem  = $this->galaxyService->getSystemByObjectCoords([$destX, $destY]);
                if (!$fleetSystem || !$destSystem || $fleetSystem->id !== $destSystem->id) {
                    return back()->withErrors(['order' => 'Interstellare Bewegung ist noch nicht verfügbar. Ziel muss im gleichen System liegen.']);
                }

                $this->fleetService->addOrder($fleet, 'move', [$destX, $destY, 0]);

            } elseif ($order === 'trade') {
                $request->validate([
                    'colony_id'   => 'required|integer',
                    'resource_id' => 'required|integer',
                    'amount'      => 'required|integer|min:1',
                    'direction'   => 'required|in:0,1',
                ]);
                $this->fleetService->addOrder($fleet, 'trade', (int) $request->input('colony_id'), [
                    'colony_id'   => (int) $request->input('colony_id'),
                    'resource_id' => (int) $request->input('resource_id'),
                    'amount'      => (int) $request->input('amount'),
                    'direction'   => (int) $request->input('direction'),
                ]);

            } elseif ($order === 'attack') {
                $request->validate(['target_fleet_id' => 'required|integer']);
                $targetFleet = Fleet::find((int) $request->input('target_fleet_id'));
                if (!$targetFleet) {
                    return back()->withErrors(['order' => 'Ziel-Flotte nicht gefunden.']);
                }
                $this->fleetService->addOrder($fleet, 'attack', $targetFleet);

            } elseif ($order === 'hold') {
                // Fleet holds its current position for one tick — no movement.
                $this->fleetService->addOrder($fleet, 'hold', [$fleet->x, $fleet->y, $fleet->spot]);

            } elseif ($order === 'convoy') {
                // Fleet escorts/follows own target fleet to its destination.
                $request->validate(['target_fleet_id' => 'required|integer']);
                $targetFleet = Fleet::where('id', (int) $request->input('target_fleet_id'))
                    ->where('user_id', $userId)->first();
                if (!$targetFleet) {
                    return back()->withErrors(['order' => 'Ziel-Flotte nicht gefunden oder gehört nicht dir.']);
                }
                $this->fleetService->addOrder($fleet, 'convoy', $targetFleet);

            } elseif ($order === 'defend') {
                // Fleet moves to the position of the target fleet to defend it (may be any fleet).
                $request->validate(['target_fleet_id' => 'required|integer']);
                $targetFleet = Fleet::find((int) $request->input('target_fleet_id'));
                if (!$targetFleet) {
                    return back()->withErrors(['order' => 'Ziel-Flotte nicht gefunden.']);
                }
                $this->fleetService->addOrder($fleet, 'defend', $targetFleet);

            } elseif ($order === 'join') {
                // Fleet moves to merge with own target fleet.
                $request->validate(['target_fleet_id' => 'required|integer']);
                $targetFleet = Fleet::where('id', (int) $request->input('target_fleet_id'))
                    ->where('user_id', $userId)->first();
                if (!$targetFleet) {
                    return back()->withErrors(['order' => 'Ziel-Flotte nicht gefunden oder gehört nicht dir.']);
                }
                $this->fleetService->addOrder($fleet, 'join', $targetFleet);
            }
        } catch (\RuntimeException $e) {
            return back()->withErrors(['order' => $e->getMessage()]);
        }

        return redirect()->route('fleet.config', $id)->with('success', 'Befehl erteilt.');
    }

    // ── JSON endpoints ────────────────────────────────────────────────────────

    public function addToFleet(Request $request, int $id): JsonResponse
    {
        $userId   = $this->getCurrentUserId();
        $fleetId  = $id;
        $itemType = $request->post('itemType');
        $itemId   = (int) $request->post('itemId', 0);
        $amount   = (int) $request->post('amount', 0);

        $fleet = Fleet::where('id', $fleetId)->where('user_id', $userId)->first();
        if (!$fleet) {
            return response()->json(['error' => 'Zugriff verweigert.'], 403);
        }
        $colony = $this->colonyService->getColonyByCoords($fleet->getCoords());

        $transferred = match (strtolower((string) $itemType)) {
            'ship'     => $this->fleetService->transferShip($colony, $fleet, $itemId, $amount),
            'research' => $this->fleetService->transferResearch($colony, $fleet, $itemId, $amount),
            'personell'=> $this->fleetService->transferPersonell($colony, $fleet, $itemId, $amount),
            'resource' => $this->fleetService->transferResource($colony, $fleet, $itemId, $amount),
            default    => 0,
        };

        return response()->json([
            'colonyId'    => $colony?->id,
            'fleetId'     => $fleetId,
            'itemType'    => $itemType,
            'itemId'      => $itemId,
            'transferred' => $transferred,
        ]);
    }

    public function getFleetTechnologies(int $id): JsonResponse
    {
        $techs = $this->fleetService->getFleetTechnologies($id);
        return response()->json($techs);
    }

    public function getFleetResources(int $id): JsonResponse
    {
        $resources    = $this->resourcesService->getResources()->keyBy('id');
        $fleetResources = $this->fleetService->getFleetResourcesByFleetId($id);

        $result = $fleetResources->keyBy('resource_id')->map(function ($row) use ($resources) {
            return array_merge($row->toArray(), [
                'name' => $resources->get($row->resource_id)?->name ?? '',
            ]);
        });

        return response()->json($result);
    }
}
