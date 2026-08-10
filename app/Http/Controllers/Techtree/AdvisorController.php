<?php

namespace App\Http\Controllers\Techtree;

use App\Http\Controllers\BaseController;
use App\Http\Controllers\Concerns\ResolvesActiveColony;
use App\Models\Advisor;
use App\Services\AdvisorService;
use App\Services\EventService;
use App\Services\OnboardingHintService;
use App\Services\ResourcesService;
use App\Services\TickService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdvisorController extends BaseController
{
    use ResolvesActiveColony;

    /**
     * Position 1 is always engineer; strategist postponed (GDD §13.6, decision 2026-08-02).
     * Slots 2–4 are path-based (scientist/pilot/trader).
     */
    private const FIXED_SLOTS = [1 => 'engineer'];

    /**
     * Maps building_id → advisor key for the three path buildings.
     * Placing one of these buildings in the colony unlocks the matching advisor slot.
     */
    private const PATH_BUILDINGS = [31 => 'scientist', 44 => 'pilot', 52 => 'trader'];

    /**
     * Advisor types whose AP pool has no consuming building yet at hire time
     * are a known trap (scientist/sciencelab, pilot/hangar — see
     * project_hint_system_review_needed memory, 2026-06-24 re-review). Maps
     * slot key → [building_id, warning lang key]. Only includes pairs where
     * the trap is real (Konsul/Cantina excluded — Cantina unlocks before the
     * Konsul slot, not after).
     */
    private const BUILDING_WARNING_MAP = [
        'scientist' => [31, 'advisors.warning_no_sciencelab'], // sciencelab
        'pilot' => [44, 'advisors.warning_no_hangar'], // hangar
    ];

    public function __construct(
        TickService $tick,
        private readonly AdvisorService $advisorService,
        private readonly ResourcesService $resourcesService,
        private readonly EventService $eventService,
        private readonly OnboardingHintService $hintService,
    ) {
        parent::__construct($tick);
    }

    /**
     * Hiring/firing an advisor changes hint state (e.g. hint_1 "Kein Baumeister
     * an Bord" clears on hire) — the hint bar only updates via the `hint:sync`
     * window event, so AJAX responses must carry the fresh hint. Mirrors
     * ColonyController::resolveHint().
     */
    private function resolveHint(int $colonyId): ?array
    {
        $hint = $this->hintService->getActiveHint($colonyId, Auth::id());
        if ($hint) {
            $hint['text'] = __($hint['text_key']);
        }

        return $hint;
    }

    /**
     * Build the canonical 5-slot array for the advisor carousel UI.
     *
     * Positions 1 and 5 are fixed (FIXED_SLOTS). Positions 2–4 are determined
     * by which path buildings (sciencelab/hangar/bar) have been placed in the
     * colony, ordered by placed_at_tick ASC. Unresolved positions show as
     * path_open until the player places the matching building.
     *
     * @param  Collection  $advisors  Active advisors on the colony (Advisor models).
     * @param  array  $slotInfo  Output of AdvisorService::getAdvisorSlotInfo().
     * @param  int  $currentTick  Current game tick for unavailability checks.
     * @param  int  $colonyId  Used for path-building lookup and building-warning checks.
     * @return array<int, array<string, mixed>>
     */
    private function buildSlots(Collection $advisors, array $slotInfo, int $currentTick, int $colonyId): array
    {
        $rankThresholds = config('game.advisor.rank_thresholds', [1 => 15, 2 => 45]);
        $upkeepMap = config('game.advisor.upkeep', [1 => 10, 2 => 30, 3 => 80]);
        $apPerRank = config('game.advisor.ap_per_rank', [1 => 4]);
        $ccLevel = $slotInfo['cc_level'];

        // Index active advisors by personell_id for O(1) lookup.
        $advisorsByPersonellId = $advisors->keyBy('personell_id');

        // Resolve path advisor keys for positions 2–4, in placement order.
        // DISTINCT ensures instanced buildings (e.g., multiple hangar instances) contribute only once.
        $pathBuildingIds = DB::table('colony_buildings')
            ->distinct()
            ->whereIn('building_id', array_keys(self::PATH_BUILDINGS))
            ->where('colony_id', $colonyId)
            ->whereNotNull('placed_at_tick')
            ->where('level', '>', 0)
            ->orderBy('placed_at_tick')
            ->orderBy('building_id')
            ->pluck('building_id')
            ->toArray();

        $pathKeys = array_map(fn ($id) => self::PATH_BUILDINGS[$id], $pathBuildingIds);

        // Preview keys for still-open path positions: whichever of the three
        // path types haven't been resolved yet, in canonical order — NOT a
        // fixed per-position map, since $pathKeys shifts left as buildings
        // are placed (bug: position 3 kept previewing 'pilot' even after
        // pilot was already resolved at position 2).
        $remainingPreviewKeys = array_values(array_diff(array_values(self::PATH_BUILDINGS), $pathKeys));

        $slots = [];

        for ($position = 1; $position <= 4; $position++) {
            // Resolve advisor key for this position.
            if (isset(self::FIXED_SLOTS[$position])) {
                $key = self::FIXED_SLOTS[$position];
            } else {
                $key = $pathKeys[$position - 2] ?? null;
            }

            // path_open: no path building placed for this slot yet.
            // All path slots require CC Lv2 minimum; the build-gate in placeBuilding()
            // controls how many path buildings can coexist (CC-Level − 1).
            if ($key === null) {
                $previewIndex = $position - 2 - count($pathKeys);
                $previewKey = $remainingPreviewKeys[$previewIndex] ?? null;
                $previewCfg = $previewKey ? config("advisors.{$previewKey}") : null;

                $slots[] = [
                    'position' => $position,
                    'key' => 'path_open_'.$position,
                    'name' => $previewKey ? trans("advisors.{$previewKey}") : 'Pfad-Berater',
                    'desc' => __('advisors.desc_path_open'),
                    'personell_id' => null,
                    'ap_type' => $previewCfg['ap_type'] ?? null,
                    'hire_cost' => 0,
                    'junior_ap' => 0,
                    'junior_upkeep' => 0,
                    'cc_required' => 2,
                    'state' => $ccLevel < 2 ? 'locked' : 'empty',
                    'advisor' => null,
                    'building_warning' => null,
                    'is_path_open' => true,
                    'preview_advisor_key' => $previewKey,
                    'path_choices' => $this->buildPathChoices($pathBuildingIds),
                ];

                continue;
            }

            $advisorCfg = config("advisors.{$key}");
            $personellId = $advisorCfg['id'];
            $hireCost = $advisorCfg['credits'];
            $apType = $advisorCfg['ap_type'];

            /** @var Advisor|null $advisor */
            $advisor = $advisorsByPersonellId->get($personellId);
            // Each slot requires CC level >= position.
            $ccGate = $position;
            $isLocked = $ccLevel < $ccGate;

            if ($isLocked) {
                $state = 'locked';
            } elseif ($advisor !== null) {
                $isUnavailable = $advisor->unavailable_until_tick !== null
                    && $advisor->unavailable_until_tick >= $currentTick;
                $state = $isUnavailable ? 'unavailable' : 'active';
            } else {
                $state = 'empty';
            }

            $advisorData = null;
            if ($advisor !== null) {
                $isMaxRank = $advisor->rank >= 3;
                $nextThreshold = $isMaxRank ? null : ($rankThresholds[$advisor->rank] ?? null);
                $isUnavailable = $advisor->unavailable_until_tick !== null
                    && $advisor->unavailable_until_tick >= $currentTick;

                if ($isMaxRank || $nextThreshold === null) {
                    $progressPct = 100;
                } else {
                    $progressPct = min(100, (int) round($advisor->active_ticks / $nextThreshold * 100));
                }

                $advisorData = [
                    'id' => $advisor->id,
                    'rank' => $advisor->rank,
                    'rank_name' => match ($advisor->rank) {
                        1 => 'Junior',
                        2 => 'Senior',
                        3 => 'Experte',
                        default => '?',
                    },
                    'ap_per_tick' => $advisor->getApPerTick(),
                    'active_ticks' => $advisor->active_ticks,
                    'progress_pct' => $progressPct,
                    'next_rank_ticks' => $nextThreshold,
                    'is_max_rank' => $isMaxRank,
                    'is_unavailable' => $isUnavailable,
                    'unavailable_until_tick' => $advisor->unavailable_until_tick,
                    'upkeep' => $upkeepMap[$advisor->rank] ?? 10,
                ];
            }

            $buildingWarning = null;
            if ($state === 'empty' && isset(self::BUILDING_WARNING_MAP[$key])) {
                [$buildingId, $warningKey] = self::BUILDING_WARNING_MAP[$key];
                $isBuilt = (int) DB::table('colony_buildings')
                    ->where('colony_id', $colonyId)
                    ->where('building_id', $buildingId)
                    ->where('level', '>', 0)
                    ->count() > 0;
                if (! $isBuilt) {
                    $buildingWarning = trans($warningKey);
                }
            }

            $slots[] = [
                'position' => $position,
                'key' => $key,
                'name' => trans("advisors.{$key}"),
                'desc' => trans("advisors.{$key}_desc"),
                'personell_id' => $personellId,
                'ap_type' => $apType,
                'hire_cost' => $hireCost,
                'junior_ap' => (int) ($apPerRank[1] ?? 4),
                'junior_upkeep' => (int) ($upkeepMap[1] ?? 10),
                'cc_required' => $ccGate,
                'state' => $state,
                'advisor' => $advisorData,
                'building_warning' => $buildingWarning,
                'is_path_open' => false,
                'preview_advisor_key' => null,
                'path_choices' => [],
            ];
        }

        return $slots;
    }

    private function buildPathChoices(array $placedBuildingIds): array
    {
        $all = [
            31 => [
                'key' => 'scientist',
                'label' => __('advisors.path_label_scientist'),
                'building' => __('techtree.building_sciencelab'),
                'image_slug' => 'sciencelab',
                'advisor' => __('advisors.scientist'),
                'unlock' => __('advisors.path_unlock_scientist'),
                'url' => '/colony/view?build=31',
                'desc' => __('advisors.path_choice_scientist'),
            ],
            44 => [
                'key' => 'pilot',
                'label' => __('advisors.path_label_pilot'),
                'building' => __('techtree.building_hangar'),
                'image_slug' => 'hangar',
                'advisor' => __('advisors.pilot'),
                'unlock' => __('advisors.path_unlock_pilot'),
                'url' => '/colony/view?build=44',
                'desc' => __('advisors.path_choice_pilot'),
            ],
            52 => [
                'key' => 'trader',
                'label' => __('advisors.path_label_trader'),
                'building' => __('techtree.building_bar'),
                'image_slug' => 'cantina',
                'advisor' => __('advisors.trader'),
                'unlock' => __('advisors.path_unlock_trader'),
                'url' => '/colony/view?build=52',
                'desc' => __('advisors.path_choice_trader'),
            ],
        ];

        return array_values(array_filter($all, fn ($k) => ! in_array($k, $placedBuildingIds), ARRAY_FILTER_USE_KEY));
    }

    public function index(): View
    {
        $colonyId = $this->resolveColonyId();
        $currentTick = $this->getTick();

        $advisors = $this->advisorService->getColonyAdvisors($colonyId);
        $slotInfo = $this->advisorService->getAdvisorSlotInfo($colonyId);
        $slots = $this->buildSlots($advisors, $slotInfo, $currentTick, $colonyId);

        $upkeepMap = config('game.advisor.upkeep', [1 => 10, 2 => 30, 3 => 80]);
        $pageData = [
            'slots' => $slots,
            'slotInfo' => $slotInfo,
            'routes' => [
                'hire' => route('advisors.hire'),
                'fire' => route('advisors.fire', ['id' => '__ID__']),
            ],
            'colonyId' => $colonyId,
            'junior_upkeep' => $upkeepMap[1] ?? 10,
        ];

        return view('advisors.index', compact('pageData'));
    }

    public function hire(Request $request): View|RedirectResponse|JsonResponse
    {
        $request->validate([
            'personell_id' => ['required', 'integer', Rule::in(AdvisorService::allIds())],
        ]);

        $colonyId = $this->resolveColonyId();
        $userId = $this->getCurrentUserId();
        $personellId = (int) $request->input('personell_id');

        $result = $this->advisorService->hire($userId, $personellId, $colonyId);

        if (is_string($result)) {
            $errorMessages = [
                'duplicate' => __('advisors.error_duplicate'),
                'slot_full' => __('advisors.error_slot_full'),
                'insufficient_credits' => __('advisors.error_insufficient_credits'),
                'dismissed_this_tick' => __('advisors.error_dismissed_this_tick'),
                'path_building_missing' => __('advisors.error_path_building_missing'),
            ];
            $errorMessage = $errorMessages[$result] ?? __('advisors.error_generic');

            if ($request->expectsJson()) {
                return response()->json(['ok' => false, 'error' => $result, 'message' => $errorMessage], 422);
            }

            return back()->with('error', $errorMessage);
        }

        $advisorCfg = collect(config('advisors'))->firstWhere(fn (array $cfg) => $cfg['id'] === $personellId) ?? [];
        $advisorType = (string) ($advisorCfg ? collect(config('advisors'))->search(fn (array $cfg) => $cfg['id'] === $personellId) : $personellId);
        $creditsCost = (int) ($advisorCfg['credits'] ?? 0);

        // Log at getTick()+1 (the target/next tick), not the current one — the
        // Sol-Report reads colony_log at the run's current_tick AFTER "Sol
        // beenden" increments it, so logging at the pre-increment tick would
        // make this event surface in a Sol-Report that already happened (same
        // off-by-one class as the stipend-purchase fix, see ColonyController).
        $this->eventService->createEvent([
            'user' => Auth::id(),
            'tick' => $this->getTick() + 1,
            'event' => 'techtree.advisor_hired',
            'area' => 'techtree',
            'parameters' => json_encode([
                'colony_id' => $colonyId,
                'advisor_type' => $advisorType,
                'credits_cost' => $creditsCost,
            ]),
        ]);

        if ($request->expectsJson()) {
            $currentTick = $this->getTick();
            $advisors = $this->advisorService->getColonyAdvisors($colonyId);
            $slotInfo = $this->advisorService->getAdvisorSlotInfo($colonyId);

            return response()->json([
                'ok' => true,
                'slots' => $this->buildSlots($advisors, $slotInfo, $currentTick, $colonyId),
                'slotInfo' => $slotInfo,
                'credits' => (int) ($this->resourcesService->getUserResources(['user_id' => $userId])->first()->credits ?? 0),
                'activeHint' => $this->resolveHint($colonyId),
            ]);
        }

        return back()->with('success', __('advisors.hired'));
    }

    public function fire(Request $request, int $id): RedirectResponse|JsonResponse
    {
        $advisor = Advisor::where('id', $id)
            ->where('user_id', $this->getCurrentUserId())
            ->first();

        if (! $advisor) {
            if ($request->expectsJson()) {
                return response()->json(['ok' => false, 'error' => 'Not found.'], 404);
            }
            abort(404);
        }

        $colonyId = (int) $advisor->colony_id;

        $this->advisorService->fire($id);

        if ($request->expectsJson()) {
            $currentTick = $this->getTick();
            $advisors = $this->advisorService->getColonyAdvisors($colonyId);
            $slotInfo = $this->advisorService->getAdvisorSlotInfo($colonyId);

            return response()->json([
                'ok' => true,
                'slots' => $this->buildSlots($advisors, $slotInfo, $currentTick, $colonyId),
                'slotInfo' => $slotInfo,
                'activeHint' => $this->resolveHint($colonyId),
            ]);
        }

        return back()->with('success', __('advisors.fired'));
    }
}
