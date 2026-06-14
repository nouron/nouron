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
    public function __construct() {}

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
                'key' => 'hint_2',
                'active' => $this->checkHint2($colonyId),
                'text_key' => 'colony.onboarding_hint_2',
                'target_url' => '/colony/view',
            ],
            [
                'rank' => 3,
                'key' => 'hint_repair',
                'active' => $this->checkHintRepair($colonyId),
                'text_key' => 'colony.onboarding_hint_repair',
                'target_url' => '/colony/view',
            ],
            [
                'rank' => 4,
                'key' => 'hint_3',
                'active' => $this->checkHint3($colonyId, $currentTick),
                'text_key' => 'colony.onboarding_hint_3',
                'target_url' => '/colony/view',
            ],
            [
                'rank' => 5,
                'key' => 'hint_4',
                'active' => $this->checkHint4($colonyId, $currentTick),
                'text_key' => 'colony.onboarding_hint_4',
                'target_url' => '/techtree',
            ],
            [
                'rank' => 6,
                'key' => 'hint_5',
                'active' => $this->checkHint5($colonyId, $currentTick),
                'text_key' => 'colony.onboarding_hint_5',
                'target_url' => '/colony/view',
            ],
            [
                'rank' => 7,
                'key' => 'hint_6',
                'active' => $this->checkHint6($colonyId, $currentTick),
                'text_key' => 'colony.onboarding_hint_6',
                'target_url' => '/colony/view?build=52',
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
            ->whereColumn('colony_buildings.status_points', '<', 'buildings.max_status_points')
            ->exists();
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
        $afterTick = (int) config('game.onboarding.hint_cc_upgrade_after_tick', 2);
        if ($currentTick < $afterTick) {
            return false;
        }

        $level = (int) DB::table('colony_buildings')
            ->where('colony_id', $colonyId)
            ->where('building_id', 25)
            ->value('level');

        return $level < 2;
    }

    /**
     * Hint 4: No knowledge researched to level > 0 AND
     *         current tick >= hint_no_knowledge_after_tick threshold.
     */
    private function checkHint4(int $colonyId, int $currentTick): bool
    {
        $threshold = (int) config('game.onboarding.hint_no_knowledge_after_tick', 10);

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
        $threshold = (int) config('game.onboarding.hint_no_cantina_after_tick', 5);

        if ($currentTick < $threshold) {
            return false;
        }

        $ccLevel = (int) DB::table('colony_buildings')
            ->where('colony_id', $colonyId)
            ->where('building_id', 25)
            ->value('level');

        if ($ccLevel < 2) {
            return false;
        }

        $housingLevel = (int) DB::table('colony_buildings')
            ->where('colony_id', $colonyId)
            ->where('building_id', 28)
            ->value('level');

        if ($housingLevel < 1) {
            return false;
        }

        $barLevel = (int) DB::table('colony_buildings')
            ->where('colony_id', $colonyId)
            ->where('building_id', 52)
            ->value('level');

        return $barLevel === 0;
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
