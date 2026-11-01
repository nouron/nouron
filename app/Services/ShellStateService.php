<?php

namespace App\Services;

use App\Enums\BuildingId;
use App\Models\Run;
use Illuminate\Support\Facades\DB;

/**
 * Header data shared by every in-run screen: resource bar, Sol/Nexus state, nav
 * gates, onboarding hint and danger notices. Feeds both the Blade layout composer
 * (keys are the view variable names) and GET /api/v1/shell (same keys, snake_case).
 */
class ShellStateService
{
    public function __construct(
        private readonly ResourcesService $resources,
        private readonly AdvisorService $advisors,
        private readonly ResourceFlowService $resourceFlow,
        private readonly TrustService $trust,
        private readonly OnboardingHintService $hints,
        private readonly EncounterNoticeService $notices,
        private readonly EventService $events,
    ) {}

    /** @return array<string, mixed> */
    public function forUser(int $userId, ?int $colonyId): array
    {
        $possessions = $colonyId ? $this->resources->getPossessionsByColonyId($colonyId) : [];
        $resourceTypes = $this->resources->getResources()->keyBy('id');
        foreach ($possessions as $resId => $poss) {
            if (isset($resourceTypes[$resId])) {
                $possessions[$resId] = array_merge($poss, $resourceTypes[$resId]->toArray());
            }
        }

        // The active run's current_tick is the canonical clock: run start (current_tick = 0) is "Sol 1".
        $activeRunTick = Run::where('user_id', $userId)->where('status', 'active')->value('current_tick');
        $solLimit = (int) config('game.run.tick_limit', 100);

        // Only runs with started_at set count as "in-run" — pending runs do not show run UI.
        // The cap comes from game.run.nexus_debt_fail_threshold, the same value the run-fail check reads.
        $activeRun = Run::where('user_id', $userId)
            ->where('status', 'active')
            ->whereNotNull('started_at')
            ->first(['nexus_debt', 'colony_id']);

        // Nav gates read the colony of the player's own run, never a session default:
        // no run in progress keeps everything gated, matching the `run.started` middleware.
        $built = fn (BuildingId $id) => DB::table('colony_buildings')
            ->where('colony_id', $activeRun?->colony_id)
            ->where('building_id', $id->value)
            ->where('level', '>', 0)
            ->exists();

        $state = [
            'resourceBarPossessions' => $possessions,
            'currentSol' => $activeRunTick !== null ? min($solLimit, max(1, (int) $activeRunTick + 1)) : null,
            'solLimit' => $solLimit,
            'nexusDebt' => $activeRun?->nexus_debt,
            'nexusDebtMax' => (int) config('game.run.nexus_debt_fail_threshold', 12000),
            'trustChipRed' => (int) config('game.run.trust_warning.chip_red', -10),
            'inActiveRun' => $activeRun !== null,
            'barBuilt' => $built(BuildingId::Bar),
            'hangarBuilt' => $built(BuildingId::Hangar),
            'sciencelabBuilt' => $built(BuildingId::Sciencelab),
            'unreadNexusCount' => $this->events->countUnreadNexus($userId),
        ];

        if (! $colonyId) {
            return $state;
        }

        $hint = $this->hints->getActiveHint($colonyId, $userId);
        if ($hint) {
            $hint['text'] = __($hint['text_key'], $hint['text_params']);
        }

        return $state + [
            'trust' => (int) (DB::table('colony_resources')->where('colony_id', $colonyId)->where('resource_id', 12)->value('amount') ?? 0),
            'colonyAp' => $this->advisors->getAvailableActionPoints($colonyId),
            'supplyBreakdown' => $this->resources->getSupplyBreakdown($colonyId),
            'colonistsOverview' => $this->resources->colonistsOverview($colonyId),
            'staffingShare' => $this->resources->staffingShare($colonyId),
            'resourceFlow' => $this->resourceFlow->perSol($colonyId),
            'hungerPenalty' => $this->trust->hungerPenalty($colonyId),
            'apBreakdown' => $this->advisors->getApBreakdown($colonyId),
            'activeHint' => $hint,
            // Danger notices (GDD §9) on the same clock as the Sol chip.
            'activeEncounterNotices' => $activeRunTick !== null
                ? $this->notices->activeNotices($colonyId, (int) $activeRunTick)
                : [],
        ];
    }
}
