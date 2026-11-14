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
            'ap_cap' => $kind === self::KIND_FAMILY ? $this->dailyApCap->state($colonyId, self::capKey($key), $this->trainingCap($colonyId)) : null,
            'unlock_level' => $unlock,
            'locked_until' => $unlock !== null && $this->hangarLevel($colonyId) < $unlock ? $unlock : null,
        ];
    }

    /**
     * Highest level the building currently allows; 0 without the building. Characters have
     * no building gate (owner 2026-11-13) — their conversation options are staged instead.
     */
    public function levelCap(int $colonyId, string $kind): int
    {
        if ($kind === self::KIND_CHARACTER) {
            return $this->maxLevel();
        }

        $buildingId = config("path_depth.buildings.{$kind}");
        if ($buildingId === null) {
            return 0;
        }

        $buildingLevel = $this->buildingLevel($colonyId, (int) $buildingId);

        $cap = 0;
        foreach (config('path_depth.building_gate') as $level => $required) {
            if ($buildingLevel >= $required) {
                $cap = max($cap, (int) $level);
            }
        }

        return min($cap, $this->maxLevel());
    }

    /**
     * @return array{level:int, ap_spend:int, ap_for_levelup:int, level_cap:int, met:bool, ap_cap:array{cap:int, spent:int, remaining:int}|null, unlock_level:int|null, locked_until:int|null, leveled_up:bool, points:int, wasted:int}
     *
     * $present: the character sits in the cantina today (CantinaPresenceService), which
     * counts as met for this conversation.
     *
     * Characters (cantina variant C, 2026-11-13): one conversation per Sol, the full price is
     * always charged; AP beyond the next level are lost and returned as `wasted`. Families
     * are still clamped to the rest of the level.
     *
     * @throws GameRuleException unknown_entity|not_met|training_locked|max_level|building_level_cap|conversation_option_locked|conversation_done_today|training_ap_cap|conversation_ap_cap|insufficient_ap
     */
    public function invest(int $colonyId, string $kind, string $key, int $points = 1, bool $present = false): array
    {
        return DB::transaction(function () use ($colonyId, $kind, $key, $points, $present): array {
            $this->assertKnown($kind, $key);
            $state = $this->state($colonyId, $kind, $key);

            // A character sitting in the cantina today can be talked to before the first
            // trade (owner 2026-11-13); the conversation below creates the relationship row.
            if (! $state['met'] && ! ($present && $kind === self::KIND_CHARACTER)) {
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

            if ($kind === self::KIND_CHARACTER && $points >= 1) {
                $required = $this->requiredCantinaLevel($points);
                if ($this->cantinaLevel($colonyId) < $required) {
                    throw $this->error('conversation_option_locked', ['level' => $required], ['requires_cantina_level' => $required]);
                }
            }

            $toNextLevel = $state['ap_for_levelup'] - $state['ap_spend'];
            if ($kind === self::KIND_CHARACTER) {
                if ($this->talkedToday($colonyId, $key)) {
                    throw $this->error('conversation_done_today');
                }
                if ($points >= 1 && $this->dailyApCap->wouldExceed($colonyId, self::conversationCapKey($key), $this->conversationCap(), $points)) {
                    throw $this->error('conversation_ap_cap', ['cap' => $this->conversationCap()]);
                }
            } else {
                $points = min($points, $toNextLevel);
                $trainingCap = $this->trainingCap($colonyId);
                if ($this->dailyApCap->wouldExceed($colonyId, self::capKey($key), $trainingCap, max(1, $points))) {
                    throw new GameRuleException('training_ap_cap', __('pathdepth.error_training_ap_cap', ['cap' => $trainingCap]));
                }
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
            $wasted = max(0, $points - $toNextLevel);
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

            return $this->state($colonyId, $kind, $key) + ['leveled_up' => $leveledUp, 'points' => $points, 'wasted' => $wasted];
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

    /** True once the character was talked to this Sol (one conversation per Sol, variant C). */
    public function talkedToday(int $colonyId, string $characterSlug): bool
    {
        return $this->dailyApCap->spent($colonyId, self::conversationCapKey($characterSlug)) > 0;
    }

    /**
     * Conversation options of a character for the cantina panel (variant C 2026-11-13).
     * "ap" is the full price an option costs (never clamped); "wasted" is how many of those
     * AP would be lost because they exceed the rest to the next level (warning chip);
     * "capped" marks an option that cannot be chosen today — the character was already
     * talked to this Sol, or the option exceeds the character's per-Sol limit.
     *
     * available / requires_cantina_level: the option is unlocked by the cantina level
     * (path_depth.conversation_option_cantina_level); a locked option carries a line of the
     * character (locked_line_key + resolved locked_line), otherwise both are null.
     *
     * @return array{cap: array{cap:int, spent:int, remaining:int}|null, talked_today: bool, to_next_level: int, options: list<array{key:string, name:string, ap:int, cost:int, wasted:int, capped:bool, available:bool, requires_cantina_level:int, locked_line_key:string|null, locked_line:string|null}>}
     */
    public function conversation(int $colonyId, string $characterSlug): array
    {
        $state = $this->state($colonyId, self::KIND_CHARACTER, $characterSlug);
        $cap = $this->dailyApCap->state($colonyId, self::conversationCapKey($characterSlug), $this->conversationCap());
        $talkedToday = $this->talkedToday($colonyId, $characterSlug);
        $toNextLevel = max(0, $state['ap_for_levelup'] - $state['ap_spend']);
        $tick = app(TickService::class)->getTickCount();
        $cantinaLevel = $this->cantinaLevel($colonyId);

        $options = [];
        if ($toNextLevel > 0) {
            foreach ((array) config('path_depth.conversation_options') as $optionKey => $cost) {
                $cost = (int) $cost;
                $required = $this->optionCantinaLevel((string) $optionKey);
                $available = $cantinaLevel >= $required;
                $lineKey = $available ? null : $this->lockedLineKey($colonyId, $characterSlug, (string) $optionKey, $tick);
                $options[] = [
                    'key' => (string) $optionKey,
                    'name' => $this->conversationName($colonyId, $characterSlug, (string) $optionKey, $tick),
                    'ap' => $cost,
                    'cost' => $cost,
                    'wasted' => max(0, $cost - $toNextLevel),
                    'capped' => $talkedToday || ($cap !== null && $cap['remaining'] < $cost),
                    'available' => $available,
                    'requires_cantina_level' => $required,
                    'locked_line_key' => $lineKey,
                    'locked_line' => $lineKey !== null ? __($lineKey, ['level' => $required]) : null,
                ];
            }
        }

        return ['cap' => $cap, 'talked_today' => $talkedToday, 'to_next_level' => $toNextLevel, 'options' => $options];
    }

    /** Current cantina level (0 without a cantina). */
    public function cantinaLevel(int $colonyId): int
    {
        return $this->buildingLevel($colonyId, (int) config('path_depth.buildings.'.self::KIND_CHARACTER));
    }

    /** Cantina level a conversation option needs (path_depth.conversation_option_cantina_level). */
    public function optionCantinaLevel(string $optionKey): int
    {
        return (int) config("path_depth.conversation_option_cantina_level.{$optionKey}", 1);
    }

    /**
     * Cantina level a conversation of $points AP needs: the level of the cheapest option
     * whose cost covers the amount, or of the largest option when none does.
     */
    public function requiredCantinaLevel(int $points): int
    {
        $costs = array_map('intval', (array) config('path_depth.conversation_options'));
        asort($costs);
        $chosen = array_key_last($costs);
        foreach ($costs as $optionKey => $cost) {
            if ($cost >= $points) {
                $chosen = $optionKey;
                break;
            }
        }

        return $chosen === null ? 1 : $this->optionCantinaLevel((string) $chosen);
    }

    /**
     * Lang key of a character line for a locked option: from the character's own pool
     * (pathdepth.option_locked_lines.<slug>) or the default pool; stable within a Sol,
     * rotating from Sol to Sol.
     */
    private function lockedLineKey(int $colonyId, string $characterSlug, string $optionKey, int $tick): string
    {
        $pool = trans("pathdepth.option_locked_lines.{$characterSlug}");
        $poolKey = is_array($pool) && $pool !== [] ? $characterSlug : 'default';
        $keys = array_keys((array) trans("pathdepth.option_locked_lines.{$poolKey}"));
        if ($keys === []) {
            return 'pathdepth.error_conversation_option_locked';
        }
        $pick = $keys[(crc32($colonyId.'|'.$characterSlug.'|'.$optionKey.'|locked') + $tick) % count($keys)];

        return "pathdepth.option_locked_lines.{$poolKey}.{$pick}";
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

    /**
     * Per-Sol AP limit per family: path_depth.training_ap_cap_per_sol.base + the pilot's
     * field bonus (AP scale 2026-11-13 §11.1); null = rule off.
     */
    public function trainingCap(int $colonyId): ?int
    {
        $base = config('path_depth.training_ap_cap_per_sol.base');

        return $base === null ? null : (int) $base + $this->advisorService->specialistCapBonus($colonyId, 'pilot');
    }

    /** Hangar level a family needs before it can be trained; null for unmapped keys. */
    public function unlockLevel(string $family): ?int
    {
        $level = config("path_depth.family_unlock_hangar_level.{$family}");

        return $level === null ? null : (int) $level;
    }

    private function hangarLevel(int $colonyId): int
    {
        return $this->buildingLevel($colonyId, (int) config('path_depth.buildings.'.self::KIND_FAMILY));
    }

    private function buildingLevel(int $colonyId, int $buildingId): int
    {
        return (int) (DB::table('colony_buildings')
            ->where('colony_id', $colonyId)
            ->where('building_id', $buildingId)
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

    /**
     * @param  array<string, mixed>  $replace
     * @param  array<string, mixed>  $context
     */
    private function error(string $code, array $replace = [], array $context = []): GameRuleException
    {
        return new GameRuleException($code, __("pathdepth.error_{$code}", $replace), $context);
    }
}
