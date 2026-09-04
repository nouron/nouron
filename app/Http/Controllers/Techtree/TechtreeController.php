<?php

namespace App\Http\Controllers\Techtree;

use App\Http\Controllers\BaseController;
use App\Http\Controllers\Concerns\ResolvesActiveColony;
use App\Services\AdvisorService;
use App\Services\OnboardingHintService;
use App\Services\Techtree\AbstractTechnologyService;
use App\Services\Techtree\BuildingService;
use App\Services\Techtree\BuildingUnlockService;
use App\Services\Techtree\KnowledgeEffectDescriptionService;
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
        private readonly AdvisorService $advisorService,
        private readonly TechtreeColonyService $techtreeColonyService,
        private readonly OnboardingHintService $onboardingHintService,
        private readonly BuildingUnlockService $buildingUnlockService,
        private readonly KnowledgeEffectDescriptionService $knowledgeEffectDescriptionService,
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

        $phases = $this->buildPhases($colonyId);

        // Onboarding pulse: ranks 2 (personell), 4 (research), 5 (buildings) highlight techtree cards.
        $userId = Auth::id();
        $hint = $userId ? $this->onboardingHintService->getActiveHint($colonyId, $userId) : null;
        $activeHintRank = ($hint && in_array($hint['rank'], [2, 4, 5])) ? $hint['rank'] : 0;

        $pageData = ['phases' => $phases];

        $firstVisit = $userId ? $this->onboardingHintService->checkFirstVisit('techtree', $userId) : false;

        return view('techtree.index', compact('pageData', 'colonyId', 'activeHintRank', 'firstVisit'));
    }

    /**
     * Build the full phases datastructure (items + within-phase dependency arrows)
     * consumed by the Alpine.js techtree view, both for the initial page render
     * (index()) and for a post-order refresh (order()) — a single AP investment
     * can flip an unrelated dependent tech's status from 'locked' to 'available'
     * (e.g. a building reaching the level another tech's required_building_id/level
     * gates on), so order() re-derives this same structure rather than patching
     * only the one invested tech. Keeping the gate logic (computeStatus()) here in
     * one place avoids duplicating it in JS, where the client doesn't even have the
     * required_building_id/level fields needed to re-evaluate it itself.
     */
    private function buildPhases(int $colonyId): array
    {
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
        $colonyAp = $this->advisorService->getAvailableActionPoints($colonyId);

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
                    // Same prerequisites as required_desc, as separate parts for a
                    // bullet list — Owner-Playtest-Fund 2026-08-31: the detail panel's
                    // "Voraussetzungen" heading already says what the line means, so
                    // repeating "Benötigt " in the value read redundant/cluttered.
                    'required_list' => $this->computeRequiredList($tech, $techtree),
                    // What this entity DOES, independent of prereqs — Owner-Playtest-Fund
                    // 2026-08-31: the sidebar showed cost/progress but never the effect,
                    // so the player couldn't plan ahead. Reuses the existing desc_techs_*
                    // texts (already written for every building/knowledge/ship, just
                    // unused here) — no new content needed for this generic-description
                    // pass. Ships were missed in the original pass (Owner-Playtest-Fund
                    // 2026-09-04) even though desc_techs_drone/corvette/freighter already
                    // existed — 'ship' just needed the same prefix-strip as the others.
                    'description' => match (true) {
                        in_array($type, ['building', 'research', 'ship'], true) => __('techtree.desc_techs_'.preg_replace('/^(building|knowledge|ship)_/', '', $tech['name'])),
                        // Advisor description reused from the Berater screen's existing
                        // *_desc lang strings (Owner-Playtest-Fund 2026-09-04) — the
                        // techtree detail panel dropped its Status/AP-type/hire-cost rows
                        // in favor of this description, same pattern as CommandCenterController.
                        (bool) $advisorKey => __('advisors.'.$advisorKey.'_desc'),
                        default => null,
                    },
                    // Reverse of required_desc: what becomes available/changes specifically
                    // at the NEXT level (Owner-Playtest-Fund 2026-08-31, e.g. "Hangar Lv2
                    // unlocks Frachter" for buildings, "-4% Bau-AP-Kosten" for knowledge —
                    // buildings have discrete gate-unlocks (BuildingUnlockService), knowledge
                    // has continuous effect curves (KnowledgeEffectDescriptionService,
                    // follow-up 2026-08-31) — same UI slot either way.
                    'unlocks_next_level' => match ($type) {
                        'building' => $this->buildingUnlockService->unlocksAtLevel((int) $id, (int) ($tech['level'] ?? 0) + 1),
                        'research' => $this->knowledgeEffectDescriptionService->effectsAtLevel(
                            preg_replace('/^knowledge_/', '', $tech['name']),
                            (int) ($tech['level'] ?? 0) + 1
                        ),
                        default => [],
                    },
                    // What the CURRENT level already delivers — same services, called at
                    // the current level instead of level+1 (Owner-Playtest-Fund 2026-09-02:
                    // sidebar showed only the next level's effect, never the active one).
                    'effects_current_level' => match ($type) {
                        'building' => $this->buildingUnlockService->unlocksAtLevel((int) $id, (int) ($tech['level'] ?? 0)),
                        'research' => $this->knowledgeEffectDescriptionService->effectsAtLevel(
                            preg_replace('/^knowledge_/', '', $tech['name']),
                            (int) ($tech['level'] ?? 0)
                        ),
                        default => [],
                    },
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
                    'ap_available' => in_array($type, ['building', 'research'], true) ? $colonyAp : 0,
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

        return $phases;
    }

    /**
     * Lightweight cross-node update payload derived from buildPhases() — only the
     * fields that can change for OTHER (non-invested) techs as a side effect of one
     * order: gate status (locked/available/built) and dependency-arrow 'met' flags.
     * Sent alongside order()'s existing single-tech 'tech' field so the client can
     * patch every dependent node in place without a full page reload or re-sending
     * the whole (translation-heavy) phases structure.
     *
     * @return array<int, array{items: list<array{id: int, type: string, status: string}>, lines: list<array>}>
     */
    private function phasesUpdatePayload(array $phases): array
    {
        $update = [];
        foreach ($phases as $phaseNum => $phase) {
            $update[$phaseNum] = [
                'items' => array_map(
                    fn (array $item) => ['id' => $item['id'], 'type' => $item['type'], 'status' => $item['status']],
                    $phase['items']
                ),
                'lines' => $phase['lines'],
            ];
        }

        return $update;
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
     * Same prerequisites as computeRequiredDesc(), split into separate parts
     * for a bullet list (no "Benötigt " prefix — the caller's heading already
     * says that). Returns [] when the tech has no building dependency.
     *
     * @return list<string>
     */
    private function computeRequiredList(array $tech, array $techtree): array
    {
        if (empty($tech['required_building_id'])) {
            return [];
        }

        $reqId = (int) $tech['required_building_id'];
        $reqLevel = (int) ($tech['required_building_level'] ?? 1);
        $reqBuilding = $techtree['building'][$reqId] ?? null;

        if (! $reqBuilding) {
            return [];
        }

        $parts = [__('techtree.'.$reqBuilding['name'])." Lv{$reqLevel}"];

        if (! empty($tech['required_building2_id'])) {
            $req2Id = (int) $tech['required_building2_id'];
            $req2Level = (int) ($tech['required_building2_level'] ?? 1);
            $req2Building = $techtree['building'][$req2Id] ?? null;
            if ($req2Building) {
                $parts[] = __('techtree.'.$req2Building['name'])." Lv{$req2Level}";
            }
        }

        return $parts;
    }

    /**
     * Resolve the techtree service for a {type} route segment.
     *
     * Only the types that actually implement the invest/levelup contract are listed.
     * `personell` is deliberately absent: AdvisorService does not extend
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
     * An `'add'` investment that reaches the AP threshold auto-triggers the levelup
     * in the same request — invest() only ever advances ap_spend, it never increments
     * the level itself, and a separate follow-up request/page-reload left the level
     * permanently stuck whenever levelupBlocker() had a reason beyond "not enough AP
     * yet" (e.g. a missing required building): ap_spend maxes out, invest() reports
     * success every time thereafter, and nothing ever explains why the level never
     * moves. Mirrors ColonyController::investBuilding()'s single-request invest+levelup
     * flow, and returns the fresh tech/AP state so the techtree screen can update in
     * place without reloading the page.
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

        $leveledUp = false;
        $levelupBlockedReason = null;

        if ($order === 'add') {
            $blocker = $service->levelupBlocker($colonyId, $id);
            if ($blocker === null) {
                $leveledUp = $service->levelup($colonyId, $id);
            } elseif ($blocker !== 'insufficient_ap_invested') {
                // Threshold not reached yet is the expected, silent case. Anything
                // else means the AP just invested is stuck behind an unmet
                // requirement — worth surfacing instead of a silent no-op.
                $levelupBlockedReason = $blocker;
            }
        }

        return response()->json([
            'success' => true,
            'order' => $order,
            'leveled_up' => $leveledUp,
            'levelup_blocked_reason' => $levelupBlockedReason,
            'levelup_blocked_message' => $levelupBlockedReason ? __("techtree.error_{$levelupBlockedReason}") : null,
            'tech' => $this->techStateFor($type, $id, $colonyId),
            'ap_available' => $this->advisorService->getAvailableActionPoints($colonyId),
            // Owner-Playtest-Fund 2026-09-04: a levelup here can flip a dependent
            // tech elsewhere in the tree from 'locked' to 'available' (or update an
            // arrow's 'met' flag) — without this the graph only reflected that after
            // a full page reload. See buildPhases()/phasesUpdatePayload() docblocks.
            'phases_update' => $this->phasesUpdatePayload($this->buildPhases($colonyId)),
        ]);
    }

    /**
     * Fresh display state for a single tech after an order — level, ap_spend, the
     * *next* level's ap_for_levelup (knowledge costs vary per level) and status, so
     * the frontend can update in place instead of reloading the page.
     */
    /**
     * Whitelisted subset of fields the frontend needs after an order — NOT the raw
     * techtree row: that carries the untranslated DB name slug (e.g. 'knowledge_
     * cartography') under 'name', which would clobber the already-translated label
     * the page rendered initially if merged in wholesale.
     */
    private function techStateFor(string $type, int $id, int $colonyId): ?array
    {
        $techtree = $this->techtreeColonyService->getTechtree($colonyId);
        $type = strtolower($type);
        $tech = $techtree[$type][$id] ?? null;

        if ($tech === null) {
            return null;
        }

        $apForLevelup = $type === 'research'
            ? $this->researchService->knowledgeLevelupCost($colonyId, $id, (int) ($tech['ap_for_levelup'] ?? 0))
            : (int) ($tech['ap_for_levelup'] ?? 0);

        return [
            'id' => $id,
            'level' => (int) ($tech['level'] ?? 0),
            'ap_spend' => (int) ($tech['ap_spend'] ?? 0),
            'ap_for_levelup' => $apForLevelup,
            'status' => $this->computeStatus($tech, $techtree),
        ];
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
