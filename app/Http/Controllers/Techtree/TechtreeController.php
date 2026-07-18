<?php

namespace App\Http\Controllers\Techtree;

use App\Http\Controllers\BaseController;
use App\Http\Controllers\Concerns\ResolvesActiveColony;
use App\Services\ColonyService;
use App\Services\OnboardingHintService;
use App\Services\ResourcesService;
use App\Services\Techtree\AbstractTechnologyService;
use App\Services\Techtree\BuildingService;
use App\Services\Techtree\PersonellService;
use App\Services\Techtree\ResearchService;
use App\Services\Techtree\ShipService;
use App\Services\Techtree\TechtreeColonyService;
use App\Services\TickService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class TechtreeController extends BaseController
{
    use ResolvesActiveColony;

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

    /**
     * Display the full techtree overview for the active colony.
     *
     * Builds $pageData with 'phases' (1-5, keyed by CC level) consumed by
     * the Alpine.js techtree view. Each phase has a 3-column grid of tech cards
     * and within-phase dependency arrows. CC arrows are omitted — the phase
     * header communicates the CC requirement.
     */
    public function index(): View|RedirectResponse
    {
        $colonyId = $this->resolveColonyId();

        $sciencelabBuilt = DB::table('colony_buildings')
            ->where('colony_id', $colonyId)
            ->where('building_id', 31)
            ->where('level', '>', 0)
            ->exists();
        if (! $sciencelabBuilt) {
            return redirect()->route('colony.view')
                ->with('info', __('colony.nav_techtree_locked'));
        }

        $techtree = $this->techtreeColonyService->getTechtree($colonyId);

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

        // Instance counts per building_id for this colony
        $instanceCounts = DB::table('colony_buildings')
            ->where('colony_id', $colonyId)
            ->select('building_id', DB::raw('COUNT(*) as cnt'))
            ->groupBy('building_id')
            ->pluck('cnt', 'building_id');

        // Total hangar instances (building_id=44) = ship capacity
        $hangarCap = (int) ($instanceCounts[44] ?? 0);

        // Available AP for sidebar invest
        $constructionAp = $this->personellService->getAvailableActionPoints('construction', $colonyId);
        $researchAp = $this->personellService->getAvailableActionPoints('research', $colonyId);

        foreach (['building', 'research', 'ship', 'personell'] as $type) {
            foreach ($techtree[$type] as $id => $tech) {
                $phaseNum = (int) ($tech['phase'] ?? 0);
                if ($phaseNum < 1 || $phaseNum > 5) {
                    continue;
                }

                $advisorKey = $type === 'personell'
                    ? str_replace('techs_', '', $tech['name'])
                    : null;
                $advisorCfg = $advisorKey ? config("advisors.{$advisorKey}", []) : [];

                $phases[$phaseNum]['items'][] = [
                    'id' => $id,
                    'type' => $type,
                    'name' => __('techtree.'.$tech['name']),
                    'level' => (int) ($tech['level'] ?? 0),
                    'row' => (int) ($tech['row'] ?? 0),
                    'col' => (int) ($tech['column'] ?? 0),
                    'status' => $this->computeStatus($tech, $techtree),
                    'required_desc' => $this->computeRequiredDesc($tech, $techtree),
                    'max_level' => isset($tech['max_level']) ? (int) $tech['max_level'] : null,
                    'key' => $type === 'building' ? $tech['name'] : null,
                    'image_slug' => $type === 'building' ? self::buildingImageSlug($tech['name']) : null,
                    'ap_type' => $advisorCfg['ap_type'] ?? null,
                    'hire_cost' => isset($advisorCfg['credits']) ? (int) $advisorCfg['credits'] : null,
                    'is_instanced' => (bool) ($tech['is_instanced'] ?? false),
                    'instance_count' => $type === 'building' ? (int) ($instanceCounts[$id] ?? 0) : 0,
                    'hangar_cap' => $type === 'ship' ? $hangarCap : null,
                    'ap_spend' => (int) ($tech['ap_spend'] ?? 0),
                    // Research/knowledge costs escalate per level (config/knowledge.php
                    // levelup_costs) — the static researches.ap_for_levelup DB column is
                    // only the Lv0→1 seed value and never reflects later levels.
                    'ap_for_levelup' => $type === 'research'
                        ? $this->researchService->knowledgeLevelupCost($colonyId, (int) $id, (int) ($tech['ap_for_levelup'] ?? 0))
                        : (int) ($tech['ap_for_levelup'] ?? 0),
                    'ap_available' => $type === 'building' ? $constructionAp
                                        : ($type === 'research' ? $researchAp : 0),
                ];

                // Generate within-phase arrow for this item.
                // Research: prefer secondary prereq building if it's in the same phase,
                //           else fall back to primary (sciencelab acts as phase-2 gatekeeper).
                // Other:    use primary prereq if it's in the same phase.
                if ($type === 'research') {
                    $fromId = null;
                    $fromLevel = 1;

                    if (! empty($tech['required_building2_id'])) {
                        $secId = (int) $tech['required_building2_id'];
                        $secPhase = (int) ($techtree['building'][$secId]['phase'] ?? 0);
                        if ($secPhase === $phaseNum && isset($techtree['building'][$secId])) {
                            $fromId = $secId;
                            $fromLevel = (int) ($tech['required_building2_level'] ?? 1);
                        }
                    }

                    if ($fromId === null && ! empty($tech['required_building_id'])) {
                        $priId = (int) $tech['required_building_id'];
                        $priPhase = (int) ($techtree['building'][$priId]['phase'] ?? 0);
                        if ($priPhase === $phaseNum && isset($techtree['building'][$priId])) {
                            $fromId = $priId;
                            $fromLevel = (int) ($tech['required_building_level'] ?? 1);
                        }
                    }

                    if ($fromId !== null) {
                        $fromBuilding = $techtree['building'][$fromId];
                        $met = (int) ($fromBuilding['level'] ?? 0) >= $fromLevel;
                        $phases[$phaseNum]['lines'][] = [
                            'from' => "tech-building-{$fromId}",
                            'to' => "tech-research-{$id}",
                            'met' => $met,
                            'label' => "Lv{$fromLevel}",
                        ];
                    }
                } else {
                    if (! empty($tech['required_building_id'])) {
                        $reqId = (int) $tech['required_building_id'];
                        $reqPhase = (int) ($techtree['building'][$reqId]['phase'] ?? 0);
                        if ($reqPhase === $phaseNum && isset($techtree['building'][$reqId])) {
                            $reqBuilding = $techtree['building'][$reqId];
                            $reqLevel = (int) ($tech['required_building_level'] ?? 1);
                            $met = (int) ($reqBuilding['level'] ?? 0) >= $reqLevel;
                            $phases[$phaseNum]['lines'][] = [
                                'from' => "tech-building-{$reqId}",
                                'to' => "tech-{$type}-{$id}",
                                'met' => $met,
                                'label' => "Lv{$reqLevel}",
                            ];
                        }
                    }
                }
            }
        }

        // Sort items within each phase by (row, col)
        foreach ($phases as &$phase) {
            usort($phase['items'], fn ($a, $b) => [$a['row'], $a['col']] <=> [$b['row'], $b['col']]);
        }
        unset($phase);

        // Onboarding pulse: ranks 2 (personell), 4 (research), 5 (buildings) highlight techtree cards.
        $userId = Auth::id();
        $hint = $userId ? $this->onboardingHintService->getActiveHint($colonyId, $userId) : null;
        $activeHintRank = ($hint && in_array($hint['rank'], [2, 4, 5])) ? $hint['rank'] : 0;

        $pageData = ['phases' => $phases];

        $firstVisit = $userId ? $this->onboardingHintService->checkFirstVisit('techtree', $userId) : false;

        return view('techtree.index', compact('pageData', 'colonyId', 'activeHintRank', 'firstVisit'));
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

        if (! empty($tech['required_building_id'])) {
            $reqId = (int) $tech['required_building_id'];
            $reqLevel = (int) ($tech['required_building_level'] ?? 1);
            $reqBuilding = $techtree['building'][$reqId] ?? null;
            if (! $reqBuilding || (int) ($reqBuilding['level'] ?? 0) < $reqLevel) {
                return 'locked';
            }
        }

        if (! empty($tech['required_building2_id'])) {
            $req2Id = (int) $tech['required_building2_id'];
            $req2Level = (int) ($tech['required_building2_level'] ?? 1);
            $req2Building = $techtree['building'][$req2Id] ?? null;
            if (! $req2Building || (int) ($req2Building['level'] ?? 0) < $req2Level) {
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

        $reqId = (int) $tech['required_building_id'];
        $reqLevel = (int) ($tech['required_building_level'] ?? 1);
        $reqBuilding = $techtree['building'][$reqId] ?? null;

        if (! $reqBuilding) {
            return null;
        }

        $desc = __('techtree.'.$reqBuilding['name'])." Lv{$reqLevel}";

        if (! empty($tech['required_building2_id'])) {
            $req2Id = (int) $tech['required_building2_id'];
            $req2Level = (int) ($tech['required_building2_level'] ?? 1);
            $req2Building = $techtree['building'][$req2Id] ?? null;
            if ($req2Building) {
                $desc .= ' + '.__('techtree.'.$req2Building['name'])." Lv{$req2Level}";
            }
        }

        return "Benötigt {$desc}";
    }

    /**
     * Return a technology detail partial (AJAX popup, no layout).
     */
    public function technology(string $type, int $id): View
    {
        $colonyId = $this->resolveColonyId();
        $techtree = $this->techtreeColonyService->getTechtree($colonyId);

        $service = match (strtolower($type)) {
            'building' => $this->buildingService,
            'research' => $this->researchService,
            'ship' => $this->shipService,
            'personell' => $this->personellService,
            default => throw new \InvalidArgumentException("Unknown type: $type"),
        };

        $apType = match (strtolower($type)) {
            'research' => 'research',
            default => 'construction',
        };

        $tech = $techtree[$type][$id] ?? null;
        if ($tech !== null && strtolower($type) === 'research') {
            $tech['ap_for_levelup'] = $this->researchService->knowledgeLevelupCost(
                $colonyId, $id, (int) ($tech['ap_for_levelup'] ?? 0)
            );
        }

        return view('techtree.technology', [
            'type' => $type,
            'techId' => $id,
            'tech' => $tech,
            'costs' => $service->getEntityCosts($id),
            'resources' => $this->resourcesService->getResources()->keyBy('id'),
            'apAvailable' => $this->personellService->getAvailableActionPoints($apType, $colonyId),
            'requiredBuildingsCheck' => $service->checkRequiredBuildingsByEntityId($colonyId, $id),
            'requiredResourcesCheck' => $this->resourcesService->check($service->getEntityCosts($id), $colonyId),
            // Passed so the view can resolve required building/research names
            'buildings' => $techtree['building'],
            'researches' => $techtree['research'],
        ]);
    }

    /**
     * Perform a techtree action via GET and return the refreshed technology partial.
     *
     * Called by techtree.js via AJAX: GET /techtree/{type}/{id}/{order}[/{ap}]
     * e.g. /techtree/building/25/add/3   or   /techtree/building/25/levelup
     */
    public function action(string $type, int $id, string $order, int $ap = 1): View
    {
        $colonyId = $this->resolveColonyId();

        // An unknown or unsupported {type} is a bad request, not a server error: the
        // route accepts any string, so this is reachable from outside. It used to throw
        // InvalidArgumentException → 500.
        $service = $this->serviceForType($type) ?? abort(404);

        match ($order) {
            'add', 'repair', 'remove' => $service->invest($colonyId, $id, $order, $ap),
            'levelup' => $service->levelup($colonyId, $id),
            'leveldown' => $service->leveldown($colonyId, $id),
            default => null,
        };

        // Re-render the technology partial so the modal reflects the updated state
        return $this->technology($type, $id);
    }

    /**
     * Resolve the techtree service for a {type} route segment.
     *
     * Only the types that actually implement the invest/levelup contract are listed.
     * `personell` is deliberately absent: PersonellService does not extend
     * AbstractTechnologyService and has no invest()/levelup() at all — mapping it here
     * turned `POST /techtree/personell/35/order` into a fatal "call to undefined method"
     * (HTTP 500). Advisors are hired through AdvisorController, not the techtree.
     */
    private function serviceForType(string $type): ?AbstractTechnologyService
    {
        return match (strtolower($type)) {
            'building' => $this->buildingService,
            'research' => $this->researchService,
            'ship' => $this->shipService,
            default => null,
        };
    }

    /**
     * Perform a techtree order (invest AP, levelup, or leveldown) via POST.
     *
     * Rejections answer 422 with a machine code in `error` and the player text in
     * `message`, like the colony and hangar endpoints. It used to answer a bare
     * `{success:false}` with no reason at all — neither the player nor a client could
     * tell "not enough AP" from "wrong Command Center level".
     *
     * `success` stays in the payload for compatibility. Note that public/js/techtree-view.js
     * does `if (!res.ok) return` — unlike the colony helpers it *does* look at the status —
     * so it now bails on 422 instead of reading `success:false`. Both paths were silent
     * (there is no else branch and no error surface in that view), so the player sees the
     * same nothing as before. Showing `message` there is a separate UI job.
     */
    public function order(Request $request, string $type, int $id): JsonResponse
    {
        $colonyId = $this->resolveColonyId();
        $order = (string) $request->input('order');
        $ap = (int) $request->input('ap', 1);

        $service = $this->serviceForType($type);

        if (! $service) {
            return $this->orderFailed('unknown_type', $order);
        }

        if (! in_array($order, ['add', 'repair', 'remove', 'levelup', 'leveldown'], true)) {
            return $this->orderFailed('unknown_order', $order);
        }

        $result = match ($order) {
            'add', 'repair', 'remove' => $service->invest($colonyId, $id, $order, $ap),
            'levelup' => $service->levelup($colonyId, $id),
            'leveldown' => $service->leveldown($colonyId, $id),
        };

        if (! $result) {
            $code = match ($order) {
                'add', 'repair', 'remove' => $service->investBlocker($colonyId, $id, $order, $ap),
                default => $service->levelupBlocker($colonyId, $id),
            };

            return $this->orderFailed($code ?? 'order_failed', $order);
        }

        return response()->json(['success' => true, 'order' => $order]);
    }

    private function orderFailed(string $code, string $order): JsonResponse
    {
        return response()->json([
            'success' => false,
            'order' => $order,
            'error' => $code,
            'message' => __("techtree.error_{$code}"),
        ], 422);
    }
}
