<?php

namespace App\Http\Controllers\Galaxy;

use App\Http\Controllers\BaseController;
use App\Models\Fleet;
use App\Services\GalaxyService;
use App\Services\TickService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * GalaxyController — Laravel port of Galaxy\Controller\IndexController,
 * Galaxy\Controller\SystemController and Galaxy\Controller\JsonController.
 *
 * Routes:
 *   GET  /galaxy                            → index()       (galaxy overview map)
 *   GET  /galaxy/system/{sid}               → showSystem()  (single-system view)
 *   GET  /galaxy/json/mapdata               → getMapData()  (AJAX tile data)
 */
class GalaxyController extends BaseController
{
    public function __construct(
        TickService $tick,
        private GalaxyService $galaxyService,
    ) {
        parent::__construct($tick);
    }

    /**
     * Galaxy overview — renders all known systems as JSON for the Leaflet map.
     * Ported from Galaxy\Controller\IndexController::indexAction().
     */
    public function index(): View
    {
        $systems = $this->galaxyService->getSystems();

        $config = [
            'range'      => config('game.galaxy_view.range',      10000),
            'offset'     => config('game.galaxy_view.offset',     0),
            'scale'      => config('game.galaxy_view.scale',      0.05),
            'systemSize' => config('game.galaxy_view.systemSize', 3),
        ];

        return view('galaxy.index', compact('systems', 'config'));
    }

    /**
     * System detail view — shows all objects, colonies and fleets inside a system.
     * Ported from Galaxy\Controller\SystemController::indexAction().
     *
     * Fleet integration is deferred (Fleet module not yet migrated).
     */
    public function showSystem(int $sid): View
    {
        $system = $this->galaxyService->getSystem($sid);
        if ($system === false) {
            abort(404, 'System not found.');
        }

        $sysCoords = [$system->x, $system->y];
        $objects   = $this->galaxyService->getObjectsByCoords($sysCoords);
        $colonies  = $this->galaxyService->getColoniesByCoords($sysCoords);

        $config = [
            'range'      => config('game.system_view.range',      100),
            'offset'     => config('game.system_view.offset',     100),
            'scale'      => config('game.system_view.scale',      10),
            'planetSize' => config('game.system_view.planetSize', 10),
            'slotSize'   => config('game.system_view.slotSize',   10),
        ];

        return view('galaxy.system', compact('system', 'objects', 'colonies', 'config'));
    }

    /**
     * AJAX endpoint — returns objects and fleets within a bounding box as JSON.
     * Ported from Galaxy\Controller\JsonController::getmapdataAction().
     *
     * Field types (asteroid fields, minefields, nebulae, graveyards) are placed
     * on layer 0 (misc layer); solid bodies (planets, giants) on layer 1.
     * Fleets go on layer 3 (not yet implemented — Fleet module pending).
     */
    public function getMapData(Request $request, mixed $x = null, mixed $y = null): JsonResponse
    {
        $x  = $x  ?? $request->query('x');
        $y  = $y  ?? $request->query('y');
        $x2 = $request->query('x2');
        $y2 = $request->query('y2');

        if (!is_numeric($x) || !is_numeric($y)) {
            return response()->json(['error' => 'Invalid coordinates', 'x' => $x, 'y' => $y], 422);
        }

        if (is_numeric($x2) && is_numeric($y2)) {
            $x = (int) round(($x + $x2) / 2);
            $y = (int) round(($y + $y2) / 2);
        }

        $objects = $this->galaxyService->getObjectsByCoords([(int) $x, (int) $y]);

        // Type IDs that represent non-solid field objects (asteroids, minefields, nebulae, graveyard)
        $fieldTypes = [9, 10, 11, 14, 15, 16];

        $data = [];

        foreach ($objects as $object) {
            $layer  = in_array($object->type_id, $fieldTypes, true) ? 0 : 1;
            $data[] = [
                'layer' => $layer,
                'x'     => $object->x,
                'y'     => $object->y,
                'attribs' => [
                    'title'     => $object->name,
                    'class'     => $object->type ?? '',
                    'image_url' => $object->image_url ?? '',
                ],
            ];
        }

        // Layer 3 — fleets within the same bounding box as the system view
        $radius  = (int) round(config('game.system_view.range', 100) / 2);
        $cx      = (int) $x;
        $cy      = (int) $y;
        $userId  = Auth::id();

        $fleets = Fleet::with('user')
            ->whereBetween('x', [$cx - $radius, $cx + $radius])
            ->whereBetween('y', [$cy - $radius, $cy + $radius])
            ->get();

        foreach ($fleets as $fleet) {
            $owner    = $fleet->user?->username ?? '?';
            $ownFleet = $fleet->user_id === $userId;
            $data[] = [
                'layer' => 3,
                'x'     => $fleet->x,
                'y'     => $fleet->y,
                'attribs' => [
                    'title'     => $fleet->fleet . ' (' . $owner . ')',
                    'class'     => $ownFleet ? 'fleet-own' : 'fleet-foreign',
                    'image_url' => '',
                ],
            ];
        }

        return response()->json($data);
    }
}
