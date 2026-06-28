<?php

namespace App\Services;

use App\Services\Techtree\PersonellService;
use Illuminate\Support\Facades\DB;

/**
 * OnboardingHintService — determines the highest-priority active onboarding hint
 * for a given colony/user combination.
 *
 * Hints are evaluated in rank order (1 = highest priority). The first active hint
 * that has not been dismissed is returned. Dismissed hints are skipped so that
 * lower-priority hints can surface when higher-priority ones have been acknowledged.
 */
class OnboardingHintService
{
    public function __construct(
        private readonly PersonellService $personellService,
        private readonly ResourcesService $resourcesService,
    ) {}

    /**
     * Returns the highest-priority active and non-dismissed hint for the given
     * colony/user, or null if onboarding hints are disabled, no hint is active,
     * or all active hints have been dismissed.
     *
     * @return array{rank: int, key: string, text_key: string, target_url: string}|null
     */
    public function getActiveHint(int $colonyId, int $userId): ?array
    {
        $prefs = DB::table('user_preferences')
            ->where('user_id', $userId)
            ->first();

        // Missing row = hints enabled (default). Bail only when explicitly disabled.
        if ($prefs && ! $prefs->onboarding_hints) {
            return null;
        }

        $dismissed = $this->parseDismissed($prefs?->dismissed_hints ?? null);

        // Use run-local Sol counter (current_tick on the active Run) so that
        // tick-threshold hints don't fire on Sol 1 due to the global tick being large.
        $run = DB::table('runs')->where('colony_id', $colonyId)->where('status', 'active')->first();
        $solTick = $run ? (int) $run->current_tick : 0;

        // Build the ordered list of hints to evaluate (rank 1 first).
        $hints = $this->buildHintList($colonyId, $solTick);

        foreach ($hints as $hint) {
            if (! $hint['active']) {
                continue;
            }

            if (in_array($hint['key'], $dismissed, true)) {
                // This hint is active but dismissed — continue to next rank.
                continue;
            }

            // Return the first active, non-dismissed hint.
            return [
                'rank' => $hint['rank'],
                'key' => $hint['key'],
                'text_key' => $hint['text_key'],
                'target_url' => $hint['target_url'],
            ];
        }

        return null;
    }

    /**
     * True the first time the given user visits a screen that should explain
     * itself via a first-visit popup (Techtree, Nexus-DB, Cantina, Hangar — see
     * resolveScreenKey() callers). Independent of the hint-bar rank list: this is
     * event-based ("screen opened"), not game-state-based, so it lives outside
     * buildHintList(). Reuses the existing dismissed_hints store with a `visit_`
     * key prefix and the existing dismissHint()/dismiss endpoint — no new schema,
     * no new route. Callers must call dismissHint($userId, "visit_{$screenKey}")
     * once the player acknowledges the popup (not on render — see dismissFirstVisit
     * for the convenience wrapper), so a reload before reading it doesn't burn it.
     */
    public function checkFirstVisit(string $screenKey, int $userId): bool
    {
        $dismissed = $this->parseDismissed(
            DB::table('user_preferences')->where('user_id', $userId)->value('dismissed_hints')
        );

        return ! in_array("visit_{$screenKey}", $dismissed, true);
    }

    /** Convenience wrapper for acknowledging a first-visit popup (see checkFirstVisit()). */
    public function dismissFirstVisit(string $screenKey, int $userId): void
    {
        $this->dismissHint($userId, "visit_{$screenKey}");
    }

    /**
     * Marks a hint as dismissed for the given user.
     * Uses updateOrInsert so it works even when no user_preferences row exists yet.
     */
    public function dismissHint(int $userId, string $hintKey): void
    {
        $prefs = DB::table('user_preferences')
            ->where('user_id', $userId)
            ->first();

        $dismissed = $this->parseDismissed($prefs->dismissed_hints ?? null);

        if (! in_array($hintKey, $dismissed, true)) {
            $dismissed[] = $hintKey;
        }

        DB::table('user_preferences')->updateOrInsert(
            ['user_id' => $userId],
            ['dismissed_hints' => json_encode(array_values($dismissed))]
        );
    }

    // ── Internal helpers ──────────────────────────────────────────────────────

    /**
     * Evaluates all hint conditions and returns an ordered list with an
     * 'active' flag for each.
     *
     * @return list<array{rank: int, key: string, active: bool, text_key: string, target_url: string}>
     */
    private function buildHintList(int $colonyId, int $currentTick): array
    {
        return [
            [
                'rank' => 1,
                'key' => 'hint_1',
                'active' => $this->checkHint1($colonyId),
                'text_key' => 'colony.onboarding_hint_1',
                'target_url' => '/advisors',
            ],
            [
                'rank' => 2,
                'key' => 'hint_repair_urgent',
                'active' => $this->checkHintRepairUrgent($colonyId),
                'text_key' => 'colony.onboarding_hint_repair_urgent',
                'target_url' => '/colony/view',
            ],
            [
                'rank' => 3,
                'key' => 'hint_2',
                'active' => $this->checkHint2($colonyId),
                'text_key' => 'colony.onboarding_hint_2',
                'target_url' => '/colony/view',
            ],
            [
                'rank' => 4,
                'key' => 'hint_repair',
                'active' => $this->checkHintRepair($colonyId),
                'text_key' => 'colony.onboarding_hint_repair',
                'target_url' => '/colony/view',
            ],
            [
                'rank' => 5,
                'key' => 'hint_3',
                'active' => $this->checkHint3($colonyId, $currentTick),
                'text_key' => $this->hint3TextKey($colonyId),
                'target_url' => $this->hint3TargetUrl($colonyId),
            ],
            [
                'rank' => 6,
                'key' => 'hint_advisor_slot2',
                'active' => $this->checkHintAdvisorSlot2($colonyId),
                'text_key' => 'colony.onboarding_hint_advisor_slot2',
                'target_url' => '/advisors',
            ],
            [
                'rank' => 7,
                'key' => 'hint_cc_invest',
                'active' => $this->checkHintCcInvest($colonyId, $currentTick),
                'text_key' => 'colony.onboarding_hint_cc_invest',
                'target_url' => '/colony/view',
            ],
            [
                'rank' => 8,
                'key' => 'hint_explore',
                'active' => $this->checkHintExplore($colonyId, $currentTick),
                'text_key' => 'colony.onboarding_hint_explore',
                'target_url' => '/colony/view',
            ],
            [
                'rank' => 9,
                'key' => 'hint_4',
                'active' => $this->checkHint4($colonyId, $currentTick),
                'text_key' => 'colony.onboarding_hint_4',
                'target_url' => '/techtree',
            ],
            [
                'rank' => 10,
                'key' => 'hint_5',
                'active' => $this->checkHint5($colonyId, $currentTick),
                'text_key' => 'colony.onboarding_hint_5',
                'target_url' => '/colony/view',
            ],
            [
                'rank' => 11,
                'key' => 'hint_build_priority',
                'active' => $this->checkHintBuildPriority($colonyId, $currentTick),
                'text_key' => 'colony.onboarding_hint_build_priority',
                'target_url' => '/colony/view',
            ],
            [
                'rank' => 12,
                'key' => 'hint_6',
                'active' => $this->checkHint6($colonyId, $currentTick),
                'text_key' => 'colony.onboarding_hint_6',
                'target_url' => '/colony/view?build=52',
            ],
            [
                'rank' => 13,
                'key' => 'hint_agrardome',
                'active' => $this->checkHintAgrardome($colonyId, $currentTick),
                'text_key' => 'colony.onboarding_hint_agrardome',
                'target_url' => '/colony/view?build=41',
            ],
            [
                'rank' => 14,
                'key' => 'hint_analytik',
                'active' => $this->checkHintAnalytik($colonyId, $currentTick),
                'text_key' => 'colony.onboarding_hint_analytik',
                'target_url' => '/colony/view?build=31',
            ],
            [
                'rank' => 15,
                'key' => 'hint_hangar_path',
                'active' => $this->checkHintHangarPath($colonyId, $currentTick),
                'text_key' => 'colony.onboarding_hint_hangar_path',
                'target_url' => '/colony/view?build=44',
            ],
            [
                'rank' => 16,
                'key' => 'hint_spend_remaining_ap',
                'active' => $this->checkHintSpendRemainingAp($colonyId, $currentTick),
                'text_key' => $this->spendRemainingApTextKey($colonyId),
                'target_url' => $this->spendRemainingApTargetUrl($colonyId),
            ],
            [
                'rank' => 17,
                'key' => 'hint_end_sol',
                'active' => $this->checkHintEndSol($colonyId, $currentTick),
                'text_key' => 'colony.onboarding_end_sol',
                'target_url' => '/colony/view',
            ],
        ];
    }

    /**
     * Hint 1: No engineer advisor active on this colony.
     * Active from Sol 1 — engineer provides build AP beyond the 6-AP base.
     */
    private function checkHint1(int $colonyId): bool
    {
        $engineerId = PersonellService::idFor('engineer');

        return DB::table('advisors')
            ->where('colony_id', $colonyId)
            ->where('personell_id', $engineerId)
            ->count() === 0;
    }

    /**
     * Urgent repair hint: a built (level >= 1) building has decayed to or below the
     * critical status-points threshold and is about to lose a level. Highest repair
     * priority (rank 2) — the only mechanic with immediate, irreversible loss.
     *
     * Self-clearing: never written to dismissed_hints, so it returns whenever decay
     * pushes a building back into the danger zone — independent of the teaching
     * hint_repair (which is dismissed permanently after the first repair click).
     */
    private function checkHintRepairUrgent(int $colonyId): bool
    {
        $threshold = (int) config('game.onboarding.hint_repair_urgent_sp', 3);

        return DB::table('colony_buildings')
            ->where('colony_id', $colonyId)
            ->where('level', '>=', 1)
            ->where('status_points', '<=', $threshold)
            ->exists();
    }

    /**
     * Repair hint: any building on the colony is below its max status points.
     * Active from Sol 1 (no tick gate); self-clears once every building is full.
     *
     * Ranked AFTER the Harvester-move hint (rank 3, not 2): repairing all three
     * damaged starting buildings costs ~12 Bau-AP, more than a single Sol provides
     * (~10 with engineer). Surfacing it first would leave the player stuck on a
     * hint they cannot resolve in Sol 1. The cheap, completable Harvester move
     * (~2 AP) goes first; the player then meets the repair goal and naturally
     * learns to spread Bau-AP across multiple Sols.
     */
    private function checkHintRepair(int $colonyId): bool
    {
        return DB::table('colony_buildings')
            ->join('buildings', 'colony_buildings.building_id', '=', 'buildings.id')
            ->where('colony_buildings.colony_id', $colonyId)
            ->where('colony_buildings.level', '>=', 1) // level 0 = under construction, not repairable
            ->whereColumn('colony_buildings.status_points', '<', 'buildings.max_status_points')
            ->exists();
    }

    /**
     * Bridge hint (lowest rank): true fallback now that hint_spend_remaining_ap
     * (rank 15) catches "AP left but no missing must-have building" — this only
     * fires once NEITHER a build-hint NOR any AP pool has anything left to spend,
     * so "alles Wichtige erledigt" is actually true when shown. Without this gate
     * it used to fire with e.g. 10 unused Bau-AP whenever Cantina/Agrardom/Analytik
     * were all three already built (Sol-5 playtest finding, 2026-06-23).
     */
    private function checkHintEndSol(int $colonyId, int $currentTick): bool
    {
        return ! $this->checkHintSpendRemainingAp($colonyId, $currentTick);
    }

    /**
     * Catch hint (rank 15): the three Sol-3-Wahlfreiheit buildings (Cantina/
     * Agrardom/Analytik) are all already placed — so the build-hints above no
     * longer have anything to suggest — but at least one AP pool still has
     * unspent points this Sol. Surfaces the pool with the most AP left so the
     * hint bar never falsely claims "nothing to do" while AP sits idle.
     *
     * Deliberately does NOT replace the build-hints: it only ever fires once
     * isBuildingPlaced() is true for all three, so a genuinely missing
     * must-have building always wins first.
     */
    private function checkHintSpendRemainingAp(int $colonyId, int $currentTick): bool
    {
        if (! $this->allChoiceBuildingsPlaced($colonyId)) {
            return false;
        }

        return $this->bestRemainingApPool($colonyId) !== null;
    }

    private function allChoiceBuildingsPlaced(int $colonyId): bool
    {
        return $this->isBuildingPlaced($colonyId, 31)  // sciencelab
            && $this->isBuildingPlaced($colonyId, 44)  // hangar
            && $this->isBuildingPlaced($colonyId, 52); // cantina
    }

    /**
     * Picks the AP pool with the most unspent points this Sol. Ties broken by
     * fixed pool priority (construction > research > navigation > economy) —
     * matches the order in which those mechanics were introduced to the player.
     * Returns null once every pool is empty (the real "nothing left" state).
     *
     * @return 'construction'|'research'|'navigation'|'economy'|null
     */
    private function bestRemainingApPool(int $colonyId): ?string
    {
        $pools = [
            'construction' => $this->personellService->getConstructionPoints($colonyId),
            'research' => $this->personellService->getResearchPoints($colonyId),
            'navigation' => $this->personellService->getAvailableActionPoints('navigation', $colonyId),
            'economy' => $this->personellService->getEconomyPoints($colonyId),
        ];

        $best = null;
        foreach ($pools as $pool => $amount) {
            if ($amount > 0 && ($best === null || $amount > $pools[$best])) {
                $best = $pool;
            }
        }

        return $best;
    }

    private function spendRemainingApTextKey(int $colonyId): string
    {
        return match ($this->bestRemainingApPool($colonyId)) {
            'research' => 'colony.onboarding_hint_spend_ap_research',
            'navigation' => 'colony.onboarding_hint_spend_ap_navigation',
            'economy' => 'colony.onboarding_hint_spend_ap_economy',
            default => 'colony.onboarding_hint_spend_ap_construction',
        };
    }

    private function spendRemainingApTargetUrl(int $colonyId): string
    {
        return match ($this->bestRemainingApPool($colonyId)) {
            'research' => '/techtree',
            'economy' => '/colony/bar',
            default => '/colony/view',
        };
    }

    /**
     * Hint 2: Harvester (building_id=27) is placed inside the colony zone (is_colony_zone=1).
     * Player should move it to the pre-explored ring-2 regolith tile outside colony borders.
     */
    private function checkHint2(int $colonyId): bool
    {
        $harvester = DB::table('colony_buildings')
            ->where('colony_id', $colonyId)
            ->where('building_id', 27)
            ->whereNotNull('tile_x')
            ->first(['tile_x', 'tile_y']);

        if (! $harvester) {
            return false;
        }

        return DB::table('colony_tiles')
            ->where('colony_id', $colonyId)
            ->where('q', $harvester->tile_x)
            ->where('r', $harvester->tile_y)
            ->where('is_colony_zone', 1)
            ->exists();
    }

    /**
     * Hint 3: CC (building_id=25) still at level 1. Fires from Sol 2 onward
     * so player has time to gather AP before the suggestion appears.
     */
    private function checkHint3(int $colonyId, int $currentTick): bool
    {
        $afterTick = (int) config('game.onboarding.hint_cc_upgrade_after_tick', 1);
        if ($currentTick < $afterTick) {
            return false;
        }

        $level = (int) DB::table('colony_buildings')
            ->where('colony_id', $colonyId)
            ->where('building_id', 25)
            ->value('level');

        return $level < 2;
    }

    private function hint3TextKey(int $colonyId): string
    {
        if (! $this->isBuildingPlaced($colonyId, 41)) {
            return 'colony.onboarding_hint_3_agrardome_first';
        }

        return 'colony.onboarding_hint_3';
    }

    private function hint3TargetUrl(int $colonyId): string
    {
        if (! $this->isBuildingPlaced($colonyId, 41)) {
            return '/colony/view?build=41';
        }

        return '/colony/view';
    }

    /**
     * Advisor-slot-2 discovery hint: CC upgrade to level 2 unlocks a second
     * advisor slot, but nothing else tells the player that — without this
     * hint, the hint bar goes silent for several Sols right after CC2 (the
     * next gated hints don't fire until Sol 3-5). No tick-gate: it's a direct
     * consequence of an action the player just took, so it should surface
     * immediately. Self-clearing via the underlying slot math — no dismiss
     * persistence needed (re-fires if the player fires the 2nd advisor again).
     */
    private function checkHintAdvisorSlot2(int $colonyId): bool
    {
        $slots = $this->personellService->getAdvisorSlotInfo($colonyId);

        // CC>=2 explicitly, not just "free>0": a fresh colony at CC1 with zero
        // advisors hired also has a free slot (slot 1) — that case is hint_1's
        // job, not this one. This hint is only about the *second* slot CC2 grants.
        return $slots['cc_level'] >= 2 && $slots['free'] > 0;
    }

    /**
     * CC pre-invest hint (rank 7, Sol 1 only): once the Sol-1 to-dos are done
     * (engineer hired, Harvester relocated, no urgent repair) and the CC is still
     * below level 2, nudge the player to sink the *remaining* Bau-AP into the CC
     * upgrade. CC level-up needs 10 Bau-AP accumulated via `ap_spend` across Sols;
     * a single Sol provides ~6–10, so pre-investing in Sol 1 guarantees level 2
     * completes in Sol 2 instead of "just barely" or slipping to Sol 3.
     *
     * Gated on "still has available construction AP this Sol" — self-clears the
     * moment the Bau-AP pool is spent, handing over to the explore hint (rank 8)
     * and ultimately the "Sol beenden" bridge (rank 14). Never persisted to
     * dismissed_hints. Sol 2+ is covered by the tick-gated hint_3.
     */
    private function checkHintCcInvest(int $colonyId, int $currentTick): bool
    {
        if ($currentTick !== 0) {
            return false;
        }

        // Must not pre-empt the prerequisite Sol-1 actions.
        if ($this->checkHint1($colonyId)
            || $this->checkHint2($colonyId)
            || $this->checkHintRepairUrgent($colonyId)) {
            return false;
        }

        // Pointless once the CC has already reached level 2.
        $ccLevel = (int) DB::table('colony_buildings')
            ->where('colony_id', $colonyId)
            ->where('building_id', 25)
            ->value('level');
        if ($ccLevel >= 2) {
            return false;
        }

        // Agrardom is a hard CC-Lv2 prerequisite — suppress the CC-invest hint until
        // Agrardom is fully built (level >= 1), not just placed as a construction site.
        // A placed-but-unfinished Agrardom means the player should invest AP there, not in CC.
        $agrardomLevel = (int) (DB::table('colony_buildings')
            ->where('colony_id', $colonyId)
            ->where('building_id', 41)
            ->whereNotNull('tile_x')
            ->value('level') ?? 0);
        if ($agrardomLevel < 1) {
            return false;
        }

        // Only while there is still Bau-AP left to pre-invest this Sol.
        return $this->personellService->getConstructionPoints($colonyId) > 0;
    }

    /**
     * Explore hint (rank 8, Sol 1 only): the base Navigation AP (6/Sol) sits idle
     * early because nothing guides the player to scout. While unexplored tiles
     * remain and Navigation AP is available, nudge exploration — it lifts fog,
     * reveals regolith for the Harvester relocation and surrounding hazards.
     *
     * Uses the existing explore mechanic (ring-staggered Nav-AP cost — see
     * game.colony.explore_cost_per_ring); the start map is seeded with reward
     * tiles (regolith). Ranked below the Bau-AP track, so the build guidance
     * (engineer/harvester/repair/CC-invest) always comes first via rank ordering;
     * explore then fills the otherwise-idle Nav-AP. Self-clearing, never
     * persisted: disappears once the Nav-AP is spent, the fog is cleared, the
     * tile-count throttle kicks in, or the Sol window has passed.
     */
    private function checkHintExplore(int $colonyId, int $currentTick): bool
    {
        $untilTick = (int) config('game.onboarding.hint_explore_until_tick', 0);
        if ($currentTick > $untilTick) {
            return false;
        }

        $hasFog = DB::table('colony_tiles')
            ->where('colony_id', $colonyId)
            ->where('is_explored', 0)
            ->exists();
        if (! $hasFog) {
            return false;
        }

        // Ring 0 (CC) and ring 1 are auto-explored at seed time — only count tiles
        // the player actually spent Nav-AP to reveal (ring >= 2).
        $maxExploredTiles = (int) config('game.onboarding.hint_explore_max_explored_tiles', 6);
        $exploredTiles = DB::table('colony_tiles')
            ->where('colony_id', $colonyId)
            ->where('is_explored', 1)
            ->where('ring', '>', 1)
            ->count();
        if ($exploredTiles >= $maxExploredTiles) {
            return false;
        }

        // Not just "any Nav-AP left" — the cheapest unexplored tile (ring-staggered
        // cost) must actually be affordable. Otherwise the hint nags the player to
        // explore with AP that can't pay for anything (e.g. 1 Nav-AP left but the
        // only remaining fog is ring 2+ at 2+ AP/tile).
        $cheapestRing = (int) DB::table('colony_tiles')
            ->where('colony_id', $colonyId)
            ->where('is_explored', 0)
            ->min('ring');
        $cheapestCost = (int) (config('game.colony.explore_cost_per_ring')[$cheapestRing]
            ?? config('game.colony.explore_cost_default', 1));

        return $this->personellService->getAvailableActionPoints('navigation', $colonyId) >= $cheapestCost;
    }

    /**
     * Hint 4: No knowledge researched to level > 0 AND
     *         current tick >= hint_no_knowledge_after_tick threshold.
     */
    private function checkHint4(int $colonyId, int $currentTick): bool
    {
        $threshold = (int) config('game.onboarding.hint_no_knowledge_after_tick', 8);

        if ($currentTick < $threshold) {
            return false;
        }

        $knowledgeIds = [90, 91, 92, 93, 94, 95, 96];

        return DB::table('colony_researches')
            ->where('colony_id', $colonyId)
            ->whereIn('research_id', $knowledgeIds)
            ->where('level', '>', 0)
            ->count() === 0;
    }

    /**
     * Hint 5: Colony trust (resource_id=12) is below the trust threshold AND
     *         current tick >= hint_trust_min_ticks threshold.
     */
    private function checkHint5(int $colonyId, int $currentTick): bool
    {
        $minTicks = (int) config('game.onboarding.hint_trust_min_ticks', 5);
        $threshold = (int) config('game.onboarding.hint_trust_threshold', -20);

        if ($currentTick < $minTicks) {
            return false;
        }

        $trust = (int) (DB::table('colony_resources')
            ->where('colony_id', $colonyId)
            ->where('resource_id', 12)
            ->value('amount') ?? 0);

        return $trust < $threshold;
    }

    /**
     * Hint 6: Cantina (building_id=52) not yet built, but prerequisites are met:
     *         CC >= level 2 AND Housing >= level 1.
     * Fires after Sol threshold to avoid showing it immediately on day 1.
     */
    private function checkHint6(int $colonyId, int $currentTick): bool
    {
        return $this->cantinaPrereqsMet($colonyId, $currentTick)
            && ! $this->isBuildingPlaced($colonyId, 52)
            && $this->canAffordBuildingPlacement($colonyId, 52);
    }

    private function cantinaPrereqsMet(int $colonyId, int $currentTick): bool
    {
        $threshold = (int) config('game.onboarding.hint_no_cantina_after_tick', 2);
        if ($currentTick < $threshold) {
            return false;
        }

        $housingLevel = (int) DB::table('colony_buildings')
            ->where('colony_id', $colonyId)
            ->where('building_id', 28)
            ->value('level');

        return $housingLevel >= 1;
    }

    /**
     * Hint Agrardom: bioFacility (building_id=41) not yet built, but prerequisite
     * is met: Harvester (building_id=27) >= level 1. Fires after Sol threshold.
     */
    private function checkHintAgrardome(int $colonyId, int $currentTick): bool
    {
        return $this->agrardomePrereqsMet($colonyId, $currentTick)
            && ! $this->isBuildingPlaced($colonyId, 41)
            && $this->canAffordBuildingPlacement($colonyId, 41);
    }

    private function agrardomePrereqsMet(int $colonyId, int $currentTick): bool
    {
        $threshold = (int) config('game.onboarding.hint_no_agrardome_after_tick', 1);
        if ($currentTick < $threshold) {
            return false;
        }

        $harvesterLevel = (int) DB::table('colony_buildings')
            ->where('colony_id', $colonyId)
            ->where('building_id', 27)
            ->value('level');

        return $harvesterLevel >= 1;
    }

    /**
     * Hint Analytik-Labor: sciencelab (building_id=31) not yet built, but
     * prerequisite is met: CC (building_id=25) >= level 2. Fires after Sol threshold.
     */
    private function checkHintAnalytik(int $colonyId, int $currentTick): bool
    {
        return $this->analytikPrereqsMet($colonyId, $currentTick)
            && ! $this->isBuildingPlaced($colonyId, 31)
            && $this->canAffordBuildingPlacement($colonyId, 31);
    }

    private function analytikPrereqsMet(int $colonyId, int $currentTick): bool
    {
        $threshold = (int) config('game.onboarding.hint_no_analytik_after_tick', 2);

        return $currentTick >= $threshold;
    }

    private function checkHintHangarPath(int $colonyId, int $currentTick): bool
    {
        return $this->hangarPrereqsMet($colonyId, $currentTick)
            && ! $this->isBuildingPlaced($colonyId, 44)
            && $this->canAffordBuildingPlacement($colonyId, 44);
    }

    private function hangarPrereqsMet(int $colonyId, int $currentTick): bool
    {
        $threshold = (int) config('game.onboarding.hint_no_hangar_after_tick', 2);

        return $currentTick >= $threshold;
    }

    /**
     * Hint: 2+ of (Cantina/Agrardom/Analytik) are simultaneously eligible
     * (prereqs met, not yet placed) but Bau-AP/Regolith won't stretch to all
     * of them this Sol — nudges the player to pick one rather than wondering
     * why only one of several "ready" buildings is being suggested. Purely
     * informational/strategic (rank 11, above the individual build hints) —
     * dismissible, doesn't block the individual hints from resuming after.
     */
    private function checkHintBuildPriority(int $colonyId, int $currentTick): bool
    {
        // Once any path building is placed the player has made a choice — hint is moot.
        if ($this->isBuildingPlaced($colonyId, 31)
            || $this->isBuildingPlaced($colonyId, 44)
            || $this->isBuildingPlaced($colonyId, 52)) {
            return false;
        }

        $eligible = 0;
        $eligible += $this->cantinaPrereqsMet($colonyId, $currentTick) ? 1 : 0;
        $eligible += $this->hangarPrereqsMet($colonyId, $currentTick) ? 1 : 0;
        $eligible += $this->analytikPrereqsMet($colonyId, $currentTick) ? 1 : 0;

        return $eligible >= 2;
    }

    /** True once a building instance has been placed on a tile (level 0 "in progress" counts). */
    private function isBuildingPlaced(int $colonyId, int $buildingId): bool
    {
        return DB::table('colony_buildings')
            ->where('colony_id', $colonyId)
            ->where('building_id', $buildingId)
            ->whereNotNull('tile_x')
            ->exists();
    }

    /**
     * "Build X" hints (Cantina/Agrardom/Analytik) must not nag the player to
     * place a building they can't currently afford — same bug class as the
     * Sol-1 Agrardom leak, generalized: checks remaining Bau-AP this Sol AND
     * Regolith/Werkstoffe/Supply against the building's actual cost
     * (config/buildings.php, canonical — mirrors ColonyController::placeBuilding).
     * Placing always costs exactly 1 Bau-AP regardless of building type.
     */
    private function canAffordBuildingPlacement(int $colonyId, int $buildingId): bool
    {
        if ($this->personellService->getConstructionPoints($colonyId) < 1) {
            return false;
        }

        $buildingKey = collect(config('buildings'))->search(fn ($cfg) => $cfg['id'] === $buildingId);
        $cfg = $buildingKey !== false ? config("buildings.{$buildingKey}") : null;
        if (! $cfg) {
            return true; // unknown building — don't block on a config lookup miss
        }

        $regolithNeeded = (int) ($cfg['build_cost'][3] ?? 0);
        $compoundsNeeded = (int) ($cfg['build_cost'][4] ?? 0);
        $supplyNeeded = (int) ($cfg['supply_cost'] ?? 0);

        if ($regolithNeeded > 0) {
            $regolith = (int) (DB::table('colony_resources')->where('colony_id', $colonyId)->where('resource_id', 3)->value('amount') ?? 0);
            if ($regolith < $regolithNeeded) {
                return false;
            }
        }

        if ($compoundsNeeded > 0) {
            $compounds = (int) (DB::table('colony_resources')->where('colony_id', $colonyId)->where('resource_id', 4)->value('amount') ?? 0);
            if ($compounds < $compoundsNeeded) {
                return false;
            }
        }

        if ($supplyNeeded > 0 && $this->resourcesService->getFreeSupply($colonyId) < $supplyNeeded) {
            return false;
        }

        return true;
    }

    /**
     * Parses the dismissed_hints JSON column value into a plain string array.
     *
     * @return list<string>
     */
    private function parseDismissed(mixed $raw): array
    {
        if (empty($raw)) {
            return [];
        }

        $decoded = json_decode($raw, true);

        if (! is_array($decoded)) {
            return [];
        }

        return array_values(array_filter($decoded, 'is_string'));
    }
}
