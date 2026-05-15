<?php

namespace App\Http\Controllers\Techtree;

use App\Http\Controllers\BaseController;
use App\Services\ColonyService;
use App\Services\OnboardingHintService;
use App\Services\ResourcesService;
use App\Services\Techtree\BuildingService;
use App\Services\Techtree\PersonellService;
use App\Services\Techtree\ResearchService;
use App\Services\Techtree\ShipService;
use App\Services\Techtree\TechtreeColonyService;
use App\Services\TickService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class TechtreeController extends BaseController
{
    public function __construct(
        TickService $tick,
        private readonly BuildingService $buildingService,
        private readonly ResearchService $researchService,
        private readonly ShipService $shipService,
        private readonly PersonellService $personellService,
        private readonly TechtreeColonyService $techtreeColonyService,
        private readonly ResourcesService $resourcesService,
        private readonly ColonyService $colonyService,
        private readonly OnboardingHintService $onboardingHintService,
    ) {
        parent::__construct($tick);
    }

    private function resolveColonyId(): int
    {
        $colonyId = Session::get('activeIds.colonyId');
        if ($colonyId) {
            return (int) $colonyId;
        }
        $userId = $this->getCurrentUserId();
        $colony = $this->colonyService->getPrimeColony($userId);
        $id = $colony->id;
        Session::put('activeIds.colonyId', $id);
        return $id;
    }

    /**
     * Display the full techtree overview for the active colony.
     *
     * Builds $pageData with 'phases' (1-5, keyed by CC level) consumed by
     * the Alpine.js techtree view. Each phase has a 3-column grid of tech cards
     * and within-phase dependency arrows. CC arrows are omitted — the phase
     * header communicates the CC requirement.
     */
    public function index(): \Illuminate\View\View
    {
        $colonyId = $this->resolveColonyId();
        $techtree  = $this->techtreeColonyService->getTechtree($colonyId);

        // Map element DOM id → phase number for same-phase arrow filtering
        $elementPhase = [];
        foreach (['building', 'research', 'ship', 'personell'] as $type) {
            foreach ($techtree[$type] as $id => $tech) {
                $phase = (int) ($tech['phase'] ?? 0);
                if ($phase > 0) {
                    $elementPhase["tech-{$type}-{$id}"] = $phase;
                }
            }
        }

        $phases = [];
        for ($n = 1; $n <= 5; $n++) {
            $phases[$n] = ['cc_level' => $n, 'items' => [], 'lines' => []];
        }

        foreach (['building', 'research', 'ship', 'personell'] as $type) {
            foreach ($techtree[$type] as $id => $tech) {
                $phaseNum = (int) ($tech['phase'] ?? 0);
                if ($phaseNum < 1 || $phaseNum > 5) {
                    continue;
                }

                $phases[$phaseNum]['items'][] = [
                    'id'            => $id,
                    'type'          => $type,
                    'name'          => __('techtree.' . $tech['name']),
                    'level'         => (int) ($tech['level'] ?? 0),
                    'row'           => (int) ($tech['row'] ?? 0),
                    'col'           => (int) ($tech['column'] ?? 0),
                    'status'        => $this->computeStatus($tech, $techtree),
                    'required_desc' => $this->computeRequiredDesc($tech, $techtree),
                    'max_level'     => isset($tech['max_level']) ? (int) $tech['max_level'] : null,
                    'key'           => $type === 'building' ? $tech['name'] : null,
                    'image_slug'    => $type === 'building' ? self::buildingImageSlug($tech['name']) : null,
                ];

                // Generate within-phase arrow for this item.
                // Research: prefer secondary prereq building if it's in the same phase,
                //           else fall back to primary (sciencelab acts as phase-2 gatekeeper).
                // Other:    use primary prereq if it's in the same phase.
                if ($type === 'research') {
                    $fromId    = null;
                    $fromLevel = 1;

                    if (!empty($tech['required_building2_id'])) {
                        $secId    = (int) $tech['required_building2_id'];
                        $secPhase = (int) ($techtree['building'][$secId]['phase'] ?? 0);
                        if ($secPhase === $phaseNum && isset($techtree['building'][$secId])) {
                            $fromId    = $secId;
                            $fromLevel = (int) ($tech['required_building2_level'] ?? 1);
                        }
                    }

                    if ($fromId === null && !empty($tech['required_building_id'])) {
                        $priId    = (int) $tech['required_building_id'];
                        $priPhase = (int) ($techtree['building'][$priId]['phase'] ?? 0);
                        if ($priPhase === $phaseNum && isset($techtree['building'][$priId])) {
                            $fromId    = $priId;
                            $fromLevel = (int) ($tech['required_building_level'] ?? 1);
                        }
                    }

                    if ($fromId !== null) {
                        $fromBuilding                  = $techtree['building'][$fromId];
                        $met                           = (int) ($fromBuilding['level'] ?? 0) >= $fromLevel;
                        $phases[$phaseNum]['lines'][] = [
                            'from'  => "tech-building-{$fromId}",
                            'to'    => "tech-research-{$id}",
                            'met'   => $met,
                            'label' => "Lv{$fromLevel}",
                        ];
                    }
                } else {
                    if (!empty($tech['required_building_id'])) {
                        $reqId    = (int) $tech['required_building_id'];
                        $reqPhase = (int) ($techtree['building'][$reqId]['phase'] ?? 0);
                        if ($reqPhase === $phaseNum && isset($techtree['building'][$reqId])) {
                            $reqBuilding                   = $techtree['building'][$reqId];
                            $reqLevel                      = (int) ($tech['required_building_level'] ?? 1);
                            $met                           = (int) ($reqBuilding['level'] ?? 0) >= $reqLevel;
                            $phases[$phaseNum]['lines'][] = [
                                'from'  => "tech-building-{$reqId}",
                                'to'    => "tech-{$type}-{$id}",
                                'met'   => $met,
                                'label' => "Lv{$reqLevel}",
                            ];
                        }
                    }
                }
            }
        }

        // Sort items within each phase by (row, col)
        foreach ($phases as &$phase) {
            usort($phase['items'], fn($a, $b) => [$a['row'], $a['col']] <=> [$b['row'], $b['col']]);
        }
        unset($phase);

        // Onboarding pulse: ranks 2 (personell), 4 (research), 5 (buildings) highlight techtree cards.
        $userId        = Auth::id();
        $hint          = $userId ? $this->onboardingHintService->getActiveHint($colonyId, $userId) : null;
        $activeHintRank = ($hint && in_array($hint['rank'], [2, 4, 5])) ? $hint['rank'] : 0;

        $pageData = ['phases' => $phases];

        return view('techtree.index', compact('pageData', 'colonyId', 'activeHintRank'));
    }

    private static function buildingImageSlug(string $key): string
    {
        $key = preg_replace('/^building_/', '', $key);
        $overrides = ['bar' => 'cantina'];
        return $overrides[$key] ?? strtolower(preg_replace('/([A-Z])/', '-$1', $key));
    }

    /**
     * Determine whether a tech is built, available, or locked.
     *
     * A tech is 'locked' when ANY of its building prerequisites are unmet.
     * Both required_building_id and required_building2_id are checked.
     */
    private function computeStatus(array $tech, array $techtree): string
    {
        if (($tech['level'] ?? 0) > 0) {
            return 'built';
        }

        if (!empty($tech['required_building_id'])) {
            $reqId       = (int) $tech['required_building_id'];
            $reqLevel    = (int) ($tech['required_building_level'] ?? 1);
            $reqBuilding = $techtree['building'][$reqId] ?? null;
            if (!$reqBuilding || (int) ($reqBuilding['level'] ?? 0) < $reqLevel) {
                return 'locked';
            }
        }

        if (!empty($tech['required_building2_id'])) {
            $req2Id       = (int) $tech['required_building2_id'];
            $req2Level    = (int) ($tech['required_building2_level'] ?? 1);
            $req2Building = $techtree['building'][$req2Id] ?? null;
            if (!$req2Building || (int) ($req2Building['level'] ?? 0) < $req2Level) {
                return 'locked';
            }
        }

        return 'available';
    }

    /**
     * Build a human-readable prerequisite description for a tech node, or null
     * when the tech has no building dependency.
     *
     * When a second prerequisite exists, both are shown joined by " + ".
     * Example: "Analytik-Labor Lv2 + Harvester Lv1"
     */
    private function computeRequiredDesc(array $tech, array $techtree): ?string
    {
        if (empty($tech['required_building_id'])) {
            return null;
        }

        $reqId       = (int) $tech['required_building_id'];
        $reqLevel    = (int) ($tech['required_building_level'] ?? 1);
        $reqBuilding = $techtree['building'][$reqId] ?? null;

        if (!$reqBuilding) {
            return null;
        }

        $desc = __('techtree.' . $reqBuilding['name']) . " Lv{$reqLevel}";

        if (!empty($tech['required_building2_id'])) {
            $req2Id       = (int) $tech['required_building2_id'];
            $req2Level    = (int) ($tech['required_building2_level'] ?? 1);
            $req2Building = $techtree['building'][$req2Id] ?? null;
            if ($req2Building) {
                $desc .= ' + ' . __('techtree.' . $req2Building['name']) . " Lv{$req2Level}";
            }
        }

        return "Benötigt {$desc}";
    }

    /**
     * Return a technology detail partial (AJAX popup, no layout).
     */
    public function technology(string $type, int $id): \Illuminate\View\View
    {
        $colonyId = $this->resolveColonyId();
        $techtree = $this->techtreeColonyService->getTechtree($colonyId);

        $service = match (strtolower($type)) {
            'building'  => $this->buildingService,
            'research'  => $this->researchService,
            'ship'      => $this->shipService,
            'personell' => $this->personellService,
            default     => throw new \InvalidArgumentException("Unknown type: $type"),
        };

        $apType = match (strtolower($type)) {
            'research' => 'research',
            default    => 'construction',
        };

        return view('techtree.technology', [
            'type'                   => $type,
            'techId'                 => $id,
            'tech'                   => $techtree[$type][$id] ?? null,
            'costs'                  => $service->getEntityCosts($id),
            'resources'              => $this->resourcesService->getResources()->keyBy('id'),
            'apAvailable'            => $this->personellService->getAvailableActionPoints($apType, $colonyId),
            'requiredBuildingsCheck' => $service->checkRequiredBuildingsByEntityId($colonyId, $id),
            'requiredResourcesCheck' => $this->resourcesService->check($service->getEntityCosts($id), $colonyId),
            // Passed so the view can resolve required building/research names
            'buildings'              => $techtree['building'],
            'researches'             => $techtree['research'],
        ]);
    }

    /**
     * Perform a techtree action via GET and return the refreshed technology partial.
     *
     * Called by techtree.js via AJAX: GET /techtree/{type}/{id}/{order}[/{ap}]
     * e.g. /techtree/building/25/add/3   or   /techtree/building/25/levelup
     */
    public function action(string $type, int $id, string $order, int $ap = 1): \Illuminate\View\View
    {
        $colonyId = $this->resolveColonyId();

        $service = match (strtolower($type)) {
            'building'  => $this->buildingService,
            'research'  => $this->researchService,
            'ship'      => $this->shipService,
            'personell' => $this->personellService,
            default     => throw new \InvalidArgumentException("Unknown type: $type"),
        };

        match ($order) {
            'add', 'repair', 'remove' => $service->invest($colonyId, $id, $order, $ap),
            'levelup'                 => $service->levelup($colonyId, $id),
            'leveldown'               => $service->leveldown($colonyId, $id),
            default                   => null,
        };

        // Re-render the technology partial so the modal reflects the updated state
        return $this->technology($type, $id);
    }

    /**
     * Perform a techtree order (invest AP, levelup, or leveldown) via POST.
     */
    public function order(Request $request, string $type, int $id): JsonResponse
    {
        $colonyId = $this->resolveColonyId();
        $order    = $request->input('order');
        $ap       = (int) $request->input('ap', 1);

        $service = match (strtolower($type)) {
            'building'  => $this->buildingService,
            'research'  => $this->researchService,
            'ship'      => $this->shipService,
            'personell' => $this->personellService,
            default     => null,
        };

        if (!$service) {
            return response()->json(['success' => false, 'message' => 'Unknown type']);
        }

        $result = match ($order) {
            'add', 'repair', 'remove' => $service->invest($colonyId, $id, $order, $ap),
            'levelup'                 => $service->levelup($colonyId, $id),
            'leveldown'               => $service->leveldown($colonyId, $id),
            default                   => false,
        };

        return response()->json(['success' => (bool) $result, 'order' => $order]);
    }
}
