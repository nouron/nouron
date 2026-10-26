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

    public function __construct(
        private readonly AdvisorService $advisorService,
        private readonly DailyApCapService $dailyApCap,
    ) {}

    /**
     * ap_cap: per-Sol training limit of a family (null = rule off / not a family);
     * unlock_level / locked_until: hangar level a family needs / that level while it is still locked.
     *
     * @return array{level:int, ap_spend:int, ap_for_levelup:int, level_cap:int, met:bool, ap_cap:array{cap:int, spent:int, remaining:int}|null, unlock_level:int|null, locked_until:int|null}
     */
    public function state(int $colonyId, string $kind, string $key): array
    {
        $row = $this->row($colonyId, $kind, $key);
        $level = $row !== null ? (int) $row->level : 0;
        $unlock = $kind === self::KIND_FAMILY ? $this->unlockLevel($key) : null;

        return [
            'level' => $level,
            'ap_spend' => $row !== null ? (int) $row->ap_spend : 0,
            'ap_for_levelup' => $level >= $this->maxLevel() ? 0 : (int) config("path_depth.levelup_ap.{$kind}.".($level + 1)),
            'level_cap' => $this->levelCap($colonyId, $kind),
            'met' => $kind === self::KIND_FAMILY || $key === self::ALWAYS_MET || $row !== null,
            'ap_cap' => $kind === self::KIND_FAMILY ? $this->dailyApCap->state($colonyId, self::capKey($key), $this->trainingCap()) : null,
            'unlock_level' => $unlock,
            'locked_until' => $unlock !== null && $this->hangarLevel($colonyId) < $unlock ? $unlock : null,
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
     * @return array{level:int, ap_spend:int, ap_for_levelup:int, level_cap:int, met:bool, ap_cap:array{cap:int, spent:int, remaining:int}|null, unlock_level:int|null, locked_until:int|null, leveled_up:bool, points:int}
     *
     * @throws GameRuleException unknown_entity|not_met|training_locked|max_level|building_level_cap|training_ap_cap|conversation_ap_cap|insufficient_ap
     */
    public function invest(int $colonyId, string $kind, string $key, int $points = 1): array
    {
        return DB::transaction(function () use ($colonyId, $kind, $key, $points): array {
            $this->assertKnown($kind, $key);
            $state = $this->state($colonyId, $kind, $key);

            if (! $state['met']) {
                throw $this->error('not_met');
            }
            if ($state['locked_until'] !== null) {
                throw $this->error('training_locked');
            }
            if ($state['level'] >= $this->maxLevel()) {
                throw $this->error('max_level');
            }
            if ($state['level'] >= $state['level_cap']) {
                throw $this->error('building_level_cap');
            }

            $points = min($points, $state['ap_for_levelup'] - $state['ap_spend']);
            if ($kind === self::KIND_CHARACTER && $points >= 1
                && $this->dailyApCap->wouldExceed($colonyId, self::conversationCapKey($key), $this->conversationCap(), $points)) {
                throw $this->error('conversation_ap_cap', ['cap' => $this->conversationCap()]);
            }
            if ($kind === self::KIND_FAMILY
                && $this->dailyApCap->wouldExceed($colonyId, self::capKey($key), $this->trainingCap(), max(1, $points))) {
                throw new GameRuleException('training_ap_cap', __('pathdepth.error_training_ap_cap', ['cap' => $this->trainingCap()]));
            }
            $bypass = (bool) config('game.bypass.ap_checks');
            if ($points < 1 || (! $bypass && $this->advisorService->getAvailableActionPoints($colonyId) < $points)) {
                throw $this->error('insufficient_ap');
            }

            if (! $bypass) {
                $this->advisorService->lockActionPoints($colonyId, $points);
            }

            if ($kind === self::KIND_CHARACTER) {
                $this->dailyApCap->record($colonyId, self::conversationCapKey($key), $points);
            }

            $apSpend = $state['ap_spend'] + $points;
            $level = $state['level'];
            $leveledUp = $apSpend >= $state['ap_for_levelup'];
            if ($leveledUp) {
                $level++;
                $apSpend = 0;
            }

            if ($kind === self::KIND_FAMILY) {
                $this->dailyApCap->record($colonyId, self::capKey($key), $points);
            }

            DB::table('colony_path_levels')->updateOrInsert(
                ['colony_id' => $colonyId, 'kind' => $kind, 'entity_key' => $key],
                ['level' => $level, 'ap_spend' => $apSpend]
            );

            return $this->state($colonyId, $kind, $key) + ['leveled_up' => $leveledUp, 'points' => $points];
        });
    }

    public static function conversationCapKey(string $characterSlug): string
    {
        return 'conversation:'.$characterSlug;
    }

    /** AP per Sol and character (path_depth.conversation_ap_cap_per_sol.base), null = rule off. */
    public function conversationCap(): ?int
    {
        $base = config('path_depth.conversation_ap_cap_per_sol.base');

        return $base === null ? null : (int) $base;
    }

    /**
     * Conversation options of a character for the cantina panel. "ap" is the real cost
     * (an option never costs more than the rest to the next level); "capped" marks options
     * that no longer fit into today's cap (shown greyed out, annahme: simpler than shortening).
     *
     * @return array{cap: array{cap:int, spent:int, remaining:int}|null, options: list<array{key:string, name:string, ap:int, cost:int, capped:bool}>}
     */
    public function conversation(int $colonyId, string $characterSlug): array
    {
        $state = $this->state($colonyId, self::KIND_CHARACTER, $characterSlug);
        $cap = $this->dailyApCap->state($colonyId, self::conversationCapKey($characterSlug), $this->conversationCap());
        $toNextLevel = max(0, $state['ap_for_levelup'] - $state['ap_spend']);
        $tick = app(TickService::class)->getTickCount();

        $options = [];
        if ($toNextLevel > 0) {
            foreach ((array) config('path_depth.conversation_options') as $optionKey => $cost) {
                $ap = min((int) $cost, $toNextLevel);
                $options[] = [
                    'key' => (string) $optionKey,
                    'name' => $this->conversationName($colonyId, $characterSlug, (string) $optionKey, $tick),
                    'ap' => $ap,
                    'cost' => (int) $cost,
                    'capped' => $cap !== null && $cap['remaining'] < $ap,
                ];
            }
        }

        return ['cap' => $cap, 'options' => $options];
    }

    /** Pool pick: stable within a Sol and for a colony/character, rotating from Sol to Sol (annahme). */
    private function conversationName(int $colonyId, string $characterSlug, string $optionKey, int $tick): string
    {
        $pool = array_values((array) trans("pathdepth.conversation_names.{$optionKey}"));
        if ($pool === []) {
            return $optionKey;
        }

        return (string) $pool[(crc32($colonyId.'|'.$characterSlug.'|'.$optionKey) + $tick) % count($pool)];
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

    public static function capKey(string $family): string
    {
        return 'training:'.$family;
    }

    /** Per-Sol AP limit per family (path_depth.training_ap_cap_per_sol.base), null = rule off. */
    public function trainingCap(): ?int
    {
        $base = config('path_depth.training_ap_cap_per_sol.base');

        return $base === null ? null : (int) $base;
    }

    /** Hangar level a family needs before it can be trained; null for unmapped keys. */
    public function unlockLevel(string $family): ?int
    {
        $level = config("path_depth.family_unlock_hangar_level.{$family}");

        return $level === null ? null : (int) $level;
    }

    private function hangarLevel(int $colonyId): int
    {
        return (int) (DB::table('colony_buildings')
            ->where('colony_id', $colonyId)
            ->where('building_id', (int) config('path_depth.buildings.'.self::KIND_FAMILY))
            ->max('level') ?? 0);
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

    /** @param  array<string, mixed>  $replace */
    private function error(string $code, array $replace = []): GameRuleException
    {
        return new GameRuleException($code, __("pathdepth.error_{$code}", $replace));
    }
}
