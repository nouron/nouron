<?php

namespace App\Http\Controllers\Techtree;

use App\Http\Controllers\BaseController;
use App\Models\Advisor;
use App\Services\ColonyService;
use App\Services\EventService;
use App\Services\ResourcesService;
use App\Services\Techtree\PersonellService;
use App\Services\TickService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdvisorController extends BaseController
{
    /**
     * Canonical advisor slot order. Position index (0-based) + 1 = position number (1–5)
     * and equals the CC level required to unlock that slot.
     */
    private const SLOT_ORDER = ['engineer', 'scientist', 'pilot', 'trader', 'strategist'];

    public function __construct(
        TickService $tick,
        private readonly PersonellService $personellService,
        private readonly ResourcesService $resourcesService,
        private readonly ColonyService $colonyService,
        private readonly EventService $eventService,
    ) {
        parent::__construct($tick);
    }

    private function resolveColonyId(): int
    {
        $colonyId = Session::get('activeIds.colonyId');
        if ($colonyId) {
            return (int) $colonyId;
        }
        $colony = $this->colonyService->getPrimeColony($this->getCurrentUserId());
        $id = $colony->id;
        Session::put('activeIds.colonyId', $id);

        return $id;
    }

    /**
     * Build the canonical 5-slot array for the advisor carousel UI.
     *
     * Each slot corresponds to one advisor type in SLOT_ORDER. Position (1–5)
     * doubles as the CC level required to unlock that slot.
     *
     * @param  Collection  $advisors  Active advisors on the colony (Advisor models).
     * @param  array  $slotInfo  Output of PersonellService::getAdvisorSlotInfo().
     * @param  int  $currentTick  Current game tick for unavailability checks.
     * @return array<int, array<string, mixed>>
     */
    private function buildSlots(Collection $advisors, array $slotInfo, int $currentTick): array
    {
        $rankThresholds = config('game.advisor.rank_thresholds', [1 => 10, 2 => 20]);
        $upkeepMap = config('game.advisor.upkeep', [1 => 10, 2 => 50, 3 => 160]);
        $apPerRank = config('game.advisor.ap_per_rank', [1 => 4]);
        $ccLevel = $slotInfo['cc_level'];

        // Index active advisors by personell_id for O(1) lookup.
        $advisorsByPersonellId = $advisors->keyBy('personell_id');

        $slots = [];

        foreach (self::SLOT_ORDER as $index => $key) {
            $position = $index + 1; // 1–5
            $advisorCfg = config("advisors.{$key}");
            $personellId = $advisorCfg['id'];
            $hireCost = $advisorCfg['credits'];
            $apType = $advisorCfg['ap_type'];

            /** @var Advisor|null $advisor */
            $advisor = $advisorsByPersonellId->get($personellId);
            $isLocked = $ccLevel < $position;

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
                    'fleet_id' => $advisor->fleet_id,
                    'is_commander' => (bool) $advisor->is_commander,
                ];
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
                'cc_required' => $position,
                'state' => $state,
                'advisor' => $advisorData,
            ];
        }

        return $slots;
    }

    public function index(): View
    {
        $colonyId = $this->resolveColonyId();
        $currentTick = $this->getTick();

        $advisors = $this->personellService->getColonyAdvisors($colonyId);
        $slotInfo = $this->personellService->getAdvisorSlotInfo($colonyId);
        $slots = $this->buildSlots($advisors, $slotInfo, $currentTick);

        $upkeepMap = config('game.advisor.upkeep', [1 => 10, 2 => 50, 3 => 160]);
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
            'personell_id' => ['required', 'integer', Rule::in(PersonellService::allIds())],
        ]);

        $colonyId = $this->resolveColonyId();
        $userId = $this->getCurrentUserId();
        $personellId = (int) $request->input('personell_id');

        $result = $this->personellService->hire($userId, $personellId, $colonyId);

        if (is_string($result)) {
            $errorMessages = [
                'duplicate' => __('advisors.error_duplicate'),
                'slot_full' => __('advisors.error_slot_full'),
                'insufficient_credits' => __('advisors.error_insufficient_credits'),
                'dismissed_this_tick' => __('advisors.error_dismissed_this_tick'),
            ];
            $errorMessage = $errorMessages[$result] ?? __('advisors.error_generic');

            if ($request->expectsJson()) {
                return response()->json(['ok' => false, 'error' => $errorMessage], 422);
            }

            return back()->with('error', $errorMessage);
        }

        $advisorCfg = collect(config('advisors'))->firstWhere(fn (array $cfg) => $cfg['id'] === $personellId) ?? [];
        $advisorType = (string) ($advisorCfg ? collect(config('advisors'))->search(fn (array $cfg) => $cfg['id'] === $personellId) : $personellId);
        $creditsCost = (int) ($advisorCfg['credits'] ?? 0);

        $this->eventService->createEvent([
            'user' => Auth::id(),
            'tick' => $this->getTick(),
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
            $advisors = $this->personellService->getColonyAdvisors($colonyId);
            $slotInfo = $this->personellService->getAdvisorSlotInfo($colonyId);

            return response()->json([
                'ok' => true,
                'slots' => $this->buildSlots($advisors, $slotInfo, $currentTick),
                'slotInfo' => $slotInfo,
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

        $this->personellService->fire($id);

        if ($request->expectsJson()) {
            $currentTick = $this->getTick();
            $advisors = $this->personellService->getColonyAdvisors($colonyId);
            $slotInfo = $this->personellService->getAdvisorSlotInfo($colonyId);

            return response()->json([
                'ok' => true,
                'slots' => $this->buildSlots($advisors, $slotInfo, $currentTick),
                'slotInfo' => $slotInfo,
            ]);
        }

        return back()->with('success', __('advisors.fired'));
    }
}
