<?php

namespace App\Services;

use App\Exceptions\GameRuleException;
use Illuminate\Support\Facades\DB;

/**
 * Path depth (T41): levels of hangar mission families and cantina characters,
 * raised with AP from the single shared pool. All numbers live in config/path_depth.php.
 */
class PathDepthService
{
    public const KIND_FAMILY = 'mission_family';

    public const KIND_CHARACTER = 'character';

    /** Character that counts as met from the start of a run. */
    private const ALWAYS_MET = 'bartender';

    public function __construct(private readonly AdvisorService $advisorService) {}

    /**
     * @return array{level:int, ap_spend:int, ap_for_levelup:int, level_cap:int, met:bool}
     */
    public function state(int $colonyId, string $kind, string $key): array
    {
        $row = $this->row($colonyId, $kind, $key);
        $level = $row !== null ? (int) $row->level : 0;

        return [
            'level' => $level,
            'ap_spend' => $row !== null ? (int) $row->ap_spend : 0,
            'ap_for_levelup' => $level >= $this->maxLevel() ? 0 : (int) config("path_depth.levelup_ap.{$kind}.".($level + 1)),
            'level_cap' => $this->levelCap($colonyId, $kind),
            'met' => $kind === self::KIND_FAMILY || $key === self::ALWAYS_MET || $row !== null,
        ];
    }

    /** Highest level the building currently allows; 0 without the building. */
    public function levelCap(int $colonyId, string $kind): int
    {
        $buildingId = config("path_depth.buildings.{$kind}");
        if ($buildingId === null) {
            return 0;
        }

        $buildingLevel = (int) (DB::table('colony_buildings')
            ->where('colony_id', $colonyId)
            ->where('building_id', (int) $buildingId)
            ->max('level') ?? 0);

        $cap = 0;
        foreach (config('path_depth.building_gate') as $level => $required) {
            if ($buildingLevel >= $required) {
                $cap = max($cap, (int) $level);
            }
        }

        return min($cap, $this->maxLevel());
    }

    /**
     * @return array{level:int, ap_spend:int, ap_for_levelup:int, level_cap:int, met:bool, leveled_up:bool, points:int}
     *
     * @throws GameRuleException unknown_entity|not_met|max_level|building_level_cap|insufficient_ap
     */
    public function invest(int $colonyId, string $kind, string $key, int $points = 1): array
    {
        return DB::transaction(function () use ($colonyId, $kind, $key, $points): array {
            $this->assertKnown($kind, $key);
            $state = $this->state($colonyId, $kind, $key);

            if (! $state['met']) {
                throw $this->error('not_met');
            }
            if ($state['level'] >= $this->maxLevel()) {
                throw $this->error('max_level');
            }
            if ($state['level'] >= $state['level_cap']) {
                throw $this->error('building_level_cap');
            }

            $points = min($points, $state['ap_for_levelup'] - $state['ap_spend']);
            $bypass = (bool) config('game.bypass.ap_checks');
            if ($points < 1 || (! $bypass && $this->advisorService->getAvailableActionPoints($colonyId) < $points)) {
                throw $this->error('insufficient_ap');
            }

            if (! $bypass) {
                $this->advisorService->lockActionPoints($colonyId, $points);
            }

            $apSpend = $state['ap_spend'] + $points;
            $level = $state['level'];
            $leveledUp = $apSpend >= $state['ap_for_levelup'];
            if ($leveledUp) {
                $level++;
                $apSpend = 0;
            }

            DB::table('colony_path_levels')->updateOrInsert(
                ['colony_id' => $colonyId, 'kind' => $kind, 'entity_key' => $key],
                ['level' => $level, 'ap_spend' => $apSpend]
            );

            return $this->state($colonyId, $kind, $key) + ['leveled_up' => $leveledUp, 'points' => $points];
        });
    }

    /** Mark a cantina character as met in this run (idempotent). */
    public function markMet(int $colonyId, string $characterSlug): void
    {
        if (config("characters.{$characterSlug}") === null) {
            return;
        }

        DB::table('colony_path_levels')->insertOrIgnore([
            'colony_id' => $colonyId,
            'kind' => self::KIND_CHARACTER,
            'entity_key' => $characterSlug,
            'level' => 0,
            'ap_spend' => 0,
        ]);
    }

    /** Payout factor of a mission: 1 + yield_per_level * level of its family. */
    public function yieldFactor(int $colonyId, string $missionKey): float
    {
        $family = $this->familyOf($missionKey);
        if ($family === null) {
            return 1.0;
        }

        $level = $this->state($colonyId, self::KIND_FAMILY, $family)['level'];

        return 1.0 + (float) config('path_depth.yield_per_level') * $level;
    }

    public function familyOf(string $missionKey): ?string
    {
        foreach (config('path_depth.families') as $family => $missions) {
            if (in_array($missionKey, $missions, true)) {
                return $family;
            }
        }

        return null;
    }

    private function maxLevel(): int
    {
        return (int) config('path_depth.max_level');
    }

    private function row(int $colonyId, string $kind, string $key): ?object
    {
        return DB::table('colony_path_levels')
            ->where(['colony_id' => $colonyId, 'kind' => $kind, 'entity_key' => $key])
            ->first();
    }

    private function assertKnown(string $kind, string $key): void
    {
        $known = match ($kind) {
            self::KIND_FAMILY => array_key_exists($key, config('path_depth.families')),
            self::KIND_CHARACTER => array_key_exists($key, config('characters', [])),
            default => false,
        };
        if (! $known) {
            throw $this->error('unknown_entity');
        }
    }

    private function error(string $code): GameRuleException
    {
        return new GameRuleException($code, __("pathdepth.error_{$code}"));
    }
}
