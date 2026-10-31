<?php

namespace App\Http\Controllers\Techtree;

use App\Enums\BuildingId;
use App\Http\Controllers\BaseController;
use App\Http\Controllers\Concerns\ResolvesActiveColony;
use App\Services\AdvisorService;
use App\Services\HangarService;
use App\Services\OnboardingHintService;
use App\Services\Techtree\BuildingService;
use App\Services\Techtree\BuildingUnlockService;
use App\Services\Techtree\KnowledgeEffectDescriptionService;
use App\Services\Techtree\ResearchService;
use App\Services\Techtree\TechtreeColonyService;
use App\Services\TickService;
use App\Support\TechtreeGraph;
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
        private readonly AdvisorService $advisorService,
        private readonly TechtreeColonyService $techtreeColonyService,
        private readonly OnboardingHintService $onboardingHintService,
        private readonly BuildingUnlockService $buildingUnlockService,
        private readonly KnowledgeEffectDescriptionService $knowledgeEffectDescriptionService,
        private readonly HangarService $hangarService,
    ) {
        parent::__construct($tick);
    }

    /**
     * Display the full techtree overview for the active colony.
     *
     * Builds $pageData with 'phases' (0-5; band 0 is the Command Center itself,
     * 1-5 are keyed by CC level) and the global 'edges' list consumed by the canvas
     * techtree view. Every item carries its grid cell (grid_row/grid_col) derived
     * from the reference data; edges come from required_building_id (solid) and
     * required_building2_id (dashed) — see App\Support\TechtreeGraph.
     */
    public function index(): View|RedirectResponse
    {
        $colonyId = $this->resolveColonyId();

        if (! $this->sciencelabBuilt($colonyId)) {
            return redirect()->route('colony.view')
                ->with('info', __('colony.nav_techtree_locked'));
        }

        ['phases' => $phases, 'edges' => $edges] = $this->buildPhases($colonyId);

        $userId = Auth::id();

        $pageData = ['phases' => $phases, 'edges' => $edges];

        $firstVisit = $userId ? $this->onboardingHintService->checkFirstVisit('techtree', $userId) : false;

        return view('techtree.index', compact('pageData', 'colonyId', 'firstVisit'));
    }

    /**
     * Same data as index() as JSON, for the SvelteKit frontend (ADR 0006).
     * The locked case answers 409 with a machine code, like the other JSON endpoints.
     */
    public function data(): JsonResponse
    {
        $colonyId = $this->resolveColonyId();

        if (! $this->sciencelabBuilt($colonyId)) {
            return response()->json([
                'error' => 'techtree_locked',
                'message' => __('colony.nav_techtree_locked'),
            ], 409);
        }

        $userId = Auth::id();

        return response()->json([
            ...$this->buildPhases($colonyId),
            'first_visit' => $userId ? $this->onboardingHintService->checkFirstVisit('techtree', $userId) : false,
        ]);
    }

    private function sciencelabBuilt(int $colonyId): bool
    {
        return DB::table('colony_buildings')
            ->where('colony_id', $colonyId)
            ->where('building_id', BuildingId::Sciencelab->value)
            ->where('level', '>', 0)
            ->exists();
    }

    /**
     * Build the full phases datastructure (items per band + global dependency edges)
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

        // Grid cell of every placed node (phase > 0 plus the Command Center as band 0).
        $graphNodes = TechtreeGraph::nodes($techtree);

        $phases = [];
        for ($n = 0; $n <= 5; $n++) {
            $phases[$n] = ['cc_level' => $n, 'items' => []];
        }

        // Instance counts per building_id for this colony
        $instanceCounts = DB::table('colony_buildings')
            ->where('colony_id', $colonyId)
            ->select('building_id', DB::raw('COUNT(*) as cnt'))
            ->groupBy('building_id')
            ->pluck('cnt', 'building_id');

        // Ship capacity = bays of the single hangar hall (T30 B1): hall level, at least
        // the apron once a hall stands.
        $hangarCap = isset($instanceCounts[44])
            ? max(1, $this->hangarService->hangarMaxLevel($colonyId))
            : 0;

        // Read-only instance list for the detail panel (A43) — actions live in the colony view.
        $placedInstances = $this->buildingService->placedInstances($colonyId);
        $maxInstances = collect(config('buildings'))
            ->filter(fn ($cfg) => is_array($cfg) && isset($cfg['id']))
            ->mapWithKeys(fn ($cfg) => [(int) $cfg['id'] => isset($cfg['max_instances']) ? (int) $cfg['max_instances'] : null]);

        // Available AP for sidebar invest
        $colonyAp = $this->advisorService->getAvailableActionPoints($colonyId);

        foreach (['building', 'research', 'ship', 'personell'] as $type) {
            foreach ($techtree[$type] as $id => $tech) {
                $graphNode = $graphNodes[TechtreeGraph::key($type, (int) $id)] ?? null;
                if ($graphNode === null) {
                    continue;
                }
                $phaseNum = $graphNode['band'];
                if ($phaseNum > 5) {
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
                    'grid_row' => $graphNode['gridRow'],
                    'grid_col' => $graphNode['gridCol'],
                    // Effective Command Center level needed (small tag on the canvas node).
                    'req_cc' => $graphNode['ccLevel'],
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
                    'hire_cost' => isset($advisorCfg['credits']) ? (int) $advisorCfg['credits'] : null,
                    'is_instanced' => (bool) ($tech['is_instanced'] ?? false),
                    'instance_count' => $type === 'building' ? (int) ($instanceCounts[$id] ?? 0) : 0,
                    'max_instances' => $type === 'building' ? ($maxInstances[(int) $id] ?? null) : null,
                    'instances' => $type === 'building' ? ($placedInstances[(int) $id] ?? []) : [],
                    // "In der Kolonie errichten" opens the build menu — only for buildings
                    // it offers; the Harvester explains how its next instance is obtained.
                    'menu_buildable' => $type === 'building' && BuildingId::isBuildMenuBuilding((int) $id),
                    'acquire_hint' => $type === 'building' && (int) $id === BuildingId::Harvester->value
                        ? __('techtree.detail_harvester_acquire_hint')
                        : null,
                    'hangar_cap' => $type === 'ship' ? $hangarCap : null,
                    'ap_spend' => (int) ($tech['ap_spend'] ?? 0),
                    // Research/knowledge costs escalate per level (config/knowledge.php
                    // levelup_costs) — the static researches.ap_for_levelup DB column is
                    // only the Lv0→1 seed value and never reflects later levels.
                    'ap_for_levelup' => $type === 'research'
                        ? $this->researchService->knowledgeLevelupCost($colonyId, (int) $id, (int) ($tech['ap_for_levelup'] ?? 0))
                        : (int) ($tech['ap_for_levelup'] ?? 0),
                    'ap_available' => in_array($type, ['building', 'research'], true) ? $colonyAp : 0,
                    // Per-Sol lab cap for this knowledge (T43-G1); null = rule off / not a knowledge.
                    'ap_cap' => $type === 'research' ? $this->researchService->apCapState($colonyId, (int) $id) : null,
                ];
            }
        }

        // Sort items within each phase by (row, col)
        foreach ($phases as &$phase) {
            usort($phase['items'], fn ($a, $b) => [$a['row'], $a['col']] <=> [$b['row'], $b['col']]);
        }
        unset($phase);

        return ['phases' => $phases, 'edges' => TechtreeGraph::edges($techtree)];
    }

    /**
     * Lightweight cross-node update payload derived from buildPhases() — only the
     * fields that can change for OTHER (non-invested) techs as a side effect of one
     * order: gate status (locked/available/built) and the edges' 'met' flags.
     * Sent alongside order()'s existing single-tech 'tech' field so the client can
     * patch every dependent node in place without a full page reload or re-sending
     * the whole (translation-heavy) phases structure.
     *
     * @param  array{phases: array, edges: list<array>}  $built
     * @return array{phases: array<int, array{items: list<array{id: int, type: string, status: string}>}>, edges: list<array>}
     */
    private function phasesUpdatePayload(array $built): array
    {
        $phases = [];
        foreach ($built['phases'] as $phaseNum => $phase) {
            $phases[$phaseNum] = [
                'items' => array_map(
                    fn (array $item) => ['id' => $item['id'], 'type' => $item['type'], 'status' => $item['status']],
                    $phase['items']
                ),
            ];
        }

        return ['phases' => $phases, 'edges' => $built['edges']];
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
     * Perform a research order (invest AP, levelup, or leveldown) via POST.
     *
     * The techtree is overview plus research only (A43): building actions live in
     * the colony view (ColonyController), ships in the hangar — `building` and
     * `ship` orders answer `use_colony_view`. `personell` is not a technology at all
     * (advisors are hired through AdvisorController) and, like any other type,
     * answers `unknown_type`.
     *
     * Rejections answer 422 with a machine code in `error` and the player text in
     * `message`, like the colony and hangar endpoints.
     *
     * An `'add'` investment that reaches the AP threshold auto-triggers the levelup
     * in the same request — invest() only ever advances ap_spend, it never increments
     * the level itself, and a separate follow-up request/page-reload left the level
     * permanently stuck whenever levelupBlocker() had a reason beyond "not enough AP
     * yet": ap_spend maxes out, invest() reports success every time thereafter, and
     * nothing ever explains why the level never moves. Returns the fresh tech/AP
     * state so the techtree screen can update in place without reloading the page.
     */
    public function order(Request $request, string $type, int $id): JsonResponse
    {
        $colonyId = $this->resolveColonyId();
        $order = (string) $request->input('order');
        $ap = (int) $request->input('ap', 1);
        $type = strtolower($type);

        if (in_array($type, ['building', 'ship'], true)) {
            return $this->orderFailed('use_colony_view', $order);
        }

        if ($type !== 'research') {
            return $this->orderFailed('unknown_type', $order);
        }

        if (! in_array($order, ['add', 'repair', 'remove', 'levelup', 'leveldown'], true)) {
            return $this->orderFailed('unknown_order', $order);
        }

        $service = $this->researchService;

        $result = match ($order) {
            'add', 'repair', 'remove' => $service->invest($colonyId, $id, $order, $ap),
            'levelup' => $service->levelup($colonyId, $id),
            'leveldown' => $service->leveldown($colonyId, $id),
        };

        if (! $result) {
            $code = match ($order) {
                'add', 'repair', 'remove' => $service->investBlocker($colonyId, $id, $order, $ap),
                'levelup' => $service->levelupBlocker($colonyId, $id),
                'leveldown' => $service->leveldownBlocker($colonyId, $id),
            };

            return $this->orderFailed($code ?? 'order_failed', $order, $code === 'knowledge_ap_cap' ? ['cap' => (int) $service->apCap()] : []);
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
            'ap_cap' => $type === 'research' ? $this->researchService->apCapState($colonyId, $id) : null,
            'status' => $this->computeStatus($tech, $techtree),
        ];
    }

    /** @param  array<string, int|string>  $replace  placeholders of the error text */
    private function orderFailed(string $code, string $order, array $replace = []): JsonResponse
    {
        return response()->json([
            'success' => false,
            'order' => $order,
            'error' => $code,
            'message' => __("techtree.error_{$code}", $replace),
        ], 422);
    }
}
