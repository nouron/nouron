<?php

namespace App\Services;

use App\Enums\BuildingId;
use App\Events\TrustEventFired;
use App\Models\Colony;
use Illuminate\Support\Facades\DB;

/**
 * TrustService — calculates and stores colony trust.
 *
 * Trust ranges from 0 to 100 (config game.trust.min/max); the base value 50
 * (game.trust.base) is part of every sum. It is recalculated every tick from static
 * sources (buildings, researches, ships), events (trust_events, with aftereffect
 * profiles) and the hunger penalty — it is not a stored balance.
 *
 * Stored in colony_resources.amount WHERE resource_id = 12 (res_trust).
 *
 * Bands (game.trust.bands, lower bound inclusive) give absolute AP:
 *   0-14 riot -2 · 15-39 restless -1 · 40-59 stable 0 · 60-79 content +1 · 80-100 euphoric +2
 * No production factor (T59, Owner 2026-11-14).
 *
 * The raw number is shown to the player only with a science lab (view()).
 * See GDD §14 and docs/superpowers/specs/2026-11-14-vertrauen-skala.md.
 */
class TrustService
{
    /** Fallback if config('game.trust.resource_id') is not set. */
    public const RESOURCE_ID = 12;

    public function __construct(
        private readonly TickService $tickService,
    ) {}

    // ── Calculation ───────────────────────────────────────────────────────────

    /**
     * Calculate trust for a colony and persist it in colony_resources.
     * Called by GameTick step 8b (after resource generation).
     */
    public function calculateAndStore(Colony $colony, int $tick): int
    {
        $trust = $this->calculateTrust($colony->id, $tick);

        DB::table('colony_resources')->updateOrInsert(
            ['colony_id' => $colony->id, 'resource_id' => $this->resourceId()],
            ['amount' => $trust]
        );

        return $trust;
    }

    /**
     * Calculate raw trust value for a colony at the given tick.
     * Does NOT persist the result.
     */
    public function calculateTrust(int $colonyId, int $tick): int
    {
        return $this->breakdown($colonyId, $tick)['total'];
    }

    /**
     * Trust split by source (single source for calculateTrust() and the trust popup:
     * displayed number = acting number). `total` is the clamped sum.
     *
     * @return array{base:int,buildings:int,research:int,ships:int,events:int,hunger:int,total:int}
     */
    public function breakdown(int $colonyId, int $tick): array
    {
        $parts = [
            'base' => $this->baseValue(),
            'buildings' => $this->buildingContribution($colonyId),
            'research' => $this->researchContribution($colonyId),
            'ships' => $this->shipContribution($colonyId),
            'events' => $this->eventContribution($colonyId, $tick),
            'hunger' => $this->hungerPenalty($colonyId),
        ];

        return $parts + ['total' => $this->clamp(array_sum($parts))];
    }

    private function baseValue(): int
    {
        return (int) config('game.trust.base', 50);
    }

    private function clamp(int $trust): int
    {
        return max((int) config('game.trust.min', 0), min((int) config('game.trust.max', 100), $trust));
    }

    /**
     * Escalating trust penalty from sustained hunger (Organika provisioning, PR 2).
     *
     * Derived deterministically from glx_colonies.hunger_streak so it escalates while
     * the colony starves and vanishes the moment it is fed again (streak reset to 0 by
     * GameTick::processFoodConsumption). Returns a non-positive value.
     */
    public function hungerPenalty(int $colonyId): int
    {
        $streak = (int) DB::table('glx_colonies')->where('id', $colonyId)->value('hunger_streak');
        if ($streak < 1) {
            return 0;
        }

        $base = (int) config('game.food.hunger_base_malus', 1);
        $step = (int) config('game.food.hunger_step', 1);
        $cap = (int) config('game.food.hunger_cap', 4);

        return -min($base + ($streak - 1) * $step, $cap);
    }

    /**
     * Read the current stored trust for a colony (0..100); the base value when no row exists.
     */
    public function getTrust(int $colonyId): int
    {
        $row = DB::table('colony_resources')
            ->where('colony_id', $colonyId)
            ->where('resource_id', $this->resourceId())
            ->first();

        return $row ? (int) $row->amount : $this->baseValue();
    }

    private function resourceId(): int
    {
        return (int) config('game.trust.resource_id', self::RESOURCE_ID);
    }

    // ── Bands ─────────────────────────────────────────────────────────────────

    /**
     * All bands ascending, each with its inclusive range and AP (single source: game.trust.bands).
     *
     * @return list<array{key: string, min: int, max: int, ap: int}>
     */
    public function bands(): array
    {
        $bands = collect(config('game.trust.bands', []))->sortBy('min')->values();
        $max = (int) config('game.trust.max', 100);

        return $bands->map(fn (array $band, int $i) => [
            'key' => (string) $band['key'],
            'min' => (int) $band['min'],
            'max' => isset($bands[$i + 1]) ? (int) $bands[$i + 1]['min'] - 1 : $max,
            'ap' => (int) $band['ap'],
        ])->all();
    }

    /**
     * The band a trust value falls into (lowest band for values below every lower bound).
     *
     * @return array{key: string, min: int, max: int, ap: int}
     */
    public function band(int $trust): array
    {
        $bands = $this->bands();
        $match = $bands[0];
        foreach ($bands as $band) {
            if ($trust >= $band['min']) {
                $match = $band;
            }
        }

        return $match;
    }

    public function bandKey(int $trust): string
    {
        return $this->band($trust)['key'];
    }

    /**
     * Lower bound of a band by key (e.g. 'content' for the prosperity objective).
     */
    public function bandMin(string $key): int
    {
        foreach ($this->bands() as $band) {
            if ($band['key'] === $key) {
                return $band['min'];
            }
        }

        throw new \InvalidArgumentException("Unknown trust band: {$key}");
    }

    /**
     * Lowest trust value whose band has no AP malus; below it the colony loses AP.
     */
    public function malusBelow(): int
    {
        foreach ($this->bands() as $band) {
            if ($band['ap'] >= 0) {
                return $band['min'];
            }
        }

        return (int) config('game.trust.max', 100) + 1;
    }

    /**
     * AP the trust band adds to (or takes from) the colony pool — absolute, game.trust.bands.
     */
    public function getApBonus(int $trust): int
    {
        return $this->band($trust)['ap'];
    }

    /**
     * Return the display name of the trust band.
     */
    public function getBand(int $trust): string
    {
        return __('trust.band_'.$this->bandKey($trust));
    }

    // ── Player view ───────────────────────────────────────────────────────────

    /**
     * Whether the colony may see trust as a number: a science lab at level >= 1
     * (T59 F3, Owner 2026-11-14). Without it no total and no source amount leaves the API.
     */
    public function numbersVisible(int $colonyId): bool
    {
        return DB::table('colony_buildings')
            ->where('colony_id', $colonyId)
            ->where('building_id', BuildingId::Sciencelab->value)
            ->where('level', '>=', 1)
            ->exists();
    }

    /**
     * Trust as the player may see it. Band, AP and the band list always; with a science lab
     * also the total (the stored, acting value) and every source with its amount. Without a
     * lab the sources are names only, and only those that currently act (base always).
     *
     * @return array{band: string, ap: int, bands: list<array{key: string, min: int, max: int, ap: int}>, lab: bool, sources: list<array{key: string, value?: int}>, total?: int}
     */
    public function view(int $colonyId, int $tick): array
    {
        $trust = $this->getTrust($colonyId);
        $band = $this->band($trust);
        $lab = $this->numbersVisible($colonyId);
        $parts = $this->breakdown($colonyId, $tick);
        unset($parts['total']);

        $sources = [];
        foreach ($parts as $key => $value) {
            if ($lab) {
                $sources[] = ['key' => $key, 'value' => $value];
            } elseif ($key === 'base' || $value !== 0) {
                $sources[] = ['key' => $key];
            }
        }

        $view = [
            'band' => $band['key'],
            'ap' => $band['ap'],
            'bands' => $this->bands(),
            'lab' => $lab,
            'sources' => $sources,
        ];
        if ($lab) {
            $view['total'] = $trust;
        }

        return $view;
    }

    // ── Events ────────────────────────────────────────────────────────────────

    /**
     * Fire a trust event for a colony.
     *
     * Events are one-shot: they contribute to exactly one tick's trust calculation.
     * For events fired outside the tick cycle (e.g. from controllers), use the
     * next tick so they are picked up at the next recalculation.
     *
     * @param  string  $eventType  Key from config('game.trust.events')
     * @param  int|null  $tick  Defaults to current tick + 1 (for out-of-tick callers).
     *                          Pass current tick when called from within GameTick.
     */
    public function fireEvent(int $colonyId, string $eventType, ?int $tick = null): void
    {
        if (! array_key_exists($eventType, config('game.trust.events', []))) {
            return; // unknown event type — silently ignore
        }

        $tick ??= $this->tickService->getTickCount() + 1;

        DB::table('trust_events')->insert([
            'colony_id' => $colonyId,
            'tick' => $tick,
            'event_type' => $eventType,
        ]);

        TrustEventFired::dispatch($colonyId, $eventType, $tick);
    }

    /**
     * Whether any of the given event types already has a trust_events row for
     * this colony at the given tick. Used to enforce "max one stipend tier per
     * Sol" (GDD §14) — different stipend event_keys do not dedupe against each
     * other in eventContribution()'s same-key collapse, so this guard is a
     * separate rule the calling code must apply before firing.
     *
     * @param  string[]  $eventTypes
     */
    public function hasEventThisTick(int $colonyId, int $tick, array $eventTypes): bool
    {
        return DB::table('trust_events')
            ->where('colony_id', $colonyId)
            ->where('tick', $tick)
            ->whereIn('event_type', $eventTypes)
            ->exists();
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function buildingContribution(int $colonyId): int
    {
        // Build [id => trust_per_lv] map from config/buildings.php
        $cfg = collect(config('buildings', []))->pluck('trust_per_lv', 'id')->filter()->toArray();
        if (empty($cfg)) {
            return 0;
        }

        $buildings = DB::table('colony_buildings')
            ->where('colony_id', $colonyId)
            ->whereIn('building_id', array_keys($cfg))
            ->where('status_points', '>', 0)
            ->get();

        $sum = 0;
        foreach ($buildings as $b) {
            $sum += ($cfg[$b->building_id] ?? 0) * $b->level;
        }

        return $sum;
    }

    private function researchContribution(int $colonyId): int
    {
        // Build [id => trust_per_lv] map from config/knowledge.php
        $cfg = collect(config('knowledge', []))->pluck('trust_per_lv', 'id')->filter()->toArray();
        if (empty($cfg)) {
            return 0;
        }

        $researches = DB::table('colony_researches')
            ->where('colony_id', $colonyId)
            ->whereIn('research_id', array_keys($cfg))
            ->get();

        $sum = 0;
        foreach ($researches as $r) {
            $sum += ($cfg[$r->research_id] ?? 0) * $r->level;
        }

        return $sum;
    }

    private function shipContribution(int $colonyId): int
    {
        // Build [id => trust_per_unit] map from config/ships.php
        $cfg = collect(config('ships', []))->pluck('trust_per_unit', 'id')->filter()->toArray();
        if (empty($cfg)) {
            return 0;
        }

        $ships = DB::table('colony_ships')
            ->where('colony_id', $colonyId)
            ->whereIn('ship_id', array_keys($cfg))
            ->get();

        $sum = 0;
        foreach ($ships as $s) {
            $sum += ($cfg[$s->ship_id] ?? 0) * $s->level;
        }

        $cap = (int) config('game.trust.ships_cap', 15);

        return max(-$cap, min($cap, $sum));
    }

    /**
     * Sum of the event part of trust for the tick: the shown lines, optionally capped.
     */
    private function eventContribution(int $colonyId, int $tick): int
    {
        $sum = array_sum(array_column($this->eventLines($colonyId, $tick), 'value'));

        $cap = config('game.trust.event_cap');
        if ($cap !== null) {
            $sum = max(-(int) $cap, min((int) $cap, $sum));
        }

        return $sum;
    }

    /**
     * Event rows that still act at $tick, one line per (type, fired Sol), with the value they
     * contribute now and the values still to come. Single source for calculateTrust() and the
     * trust popup (displayed number = acting number). Same type fired in the same Sol: strongest
     * wins; different Sols add up. Security Hub mitigation (absolute) applies to the delta before the profile.
     *
     * @return list<array{type: string, tick: int, age: int, value: int, following: list<int>}>
     */
    public function eventLines(int $colonyId, int $tick): array
    {
        $cfg = config('game.trust.events', []);
        if (empty($cfg)) {
            return [];
        }

        $profiles = config('game.trust.event_profiles', ['instant' => [100]]);
        $assigned = config('game.trust.event_profile', []);
        $profileOf = fn (string $type): array => $profiles[$assigned[$type] ?? 'instant'] ?? [100];

        $window = max(array_map('count', $profiles) ?: [1]);

        $rows = DB::table('trust_events')
            ->where('colony_id', $colonyId)
            ->whereBetween('tick', [$tick - $window + 1, $tick])
            ->get(['tick', 'event_type']);

        // Group by (type, fired Sol), keep strongest delta (by abs value)
        $byKey = [];
        foreach ($rows as $row) {
            $delta = $cfg[$row->event_type] ?? 0;
            $key = $row->event_type.'|'.$row->tick;
            if (! isset($byKey[$key]) || abs($delta) > abs($byKey[$key]['delta'])) {
                $byKey[$key] = ['type' => $row->event_type, 'tick' => (int) $row->tick, 'delta' => $delta];
            }
        }

        // SecurityHub (building_id=53) moves negative trust events a fixed amount closer to 0.
        $mitigation = (int) config('buildings.securityHub.trust_event_mitigation', 0);
        $mitigate = $mitigation > 0 && DB::table('colony_buildings')
            ->where('colony_id', $colonyId)
            ->where('building_id', 53)
            ->where('level', '>', 0)
            ->exists();

        $lines = [];
        foreach ($byKey as $e) {
            $delta = $e['delta'];
            if ($mitigate && $delta < 0) {
                $delta = min(0, $delta + $mitigation);
            }

            $profile = $profileOf($e['type']);
            $age = $tick - $e['tick'];
            if (! isset($profile[$age])) {
                continue;
            }

            // Each line is rounded on its own (away from zero) so lines and sum always agree.
            $valueAt = fn (int $i): int => (int) round($delta * $profile[$i] / 100);
            $value = $valueAt($age);
            $following = [];
            for ($i = $age + 1; $i < count($profile); $i++) {
                $following[] = $valueAt($i);
            }

            if ($value === 0 && array_sum($following) === 0) {
                continue;
            }

            $lines[] = ['type' => $e['type'], 'tick' => $e['tick'], 'age' => $age, 'value' => $value, 'following' => $following];
        }

        return $lines;
    }
}
