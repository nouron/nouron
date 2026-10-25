<?php

namespace App\Services;

use App\Events\TrustEventFired;
use App\Models\Colony;
use Illuminate\Support\Facades\DB;

/**
 * TrustService — calculates and stores colony trust.
 *
 * Trust ranges from -100 to +100 (neutral = 0, start = 0).
 * It is recalculated every tick from static factors (buildings, researches, ships)
 * and one-shot events (stored in trust_events for the current tick).
 *
 * Stored in colony_resources.amount WHERE resource_id = 12 (res_trust).
 *
 * Bands:
 *   +61 .. +100  Euphorisch
 *   +21 .. +60   Zufrieden
 *   -20 .. +20   Stabil
 *   -21 .. -60   Unruhig
 *   -61 .. -100  Aufruhr
 *
 * Effects (applied by callers):
 *   - Production multiplier: 0.70× (Aufruhr) .. 1.20× (Euphorisch)
 *   - AP multiplier:         0.80× (Aufruhr) .. 1.10× (Euphorisch)
 *
 * See GDD §13 for full design documentation.
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
        $trust = 0;
        $trust += $this->buildingContribution($colonyId);
        $trust += $this->researchContribution($colonyId);
        $trust += $this->shipContribution($colonyId);
        $trust += $this->eventContribution($colonyId, $tick);
        $trust += $this->hungerPenalty($colonyId);

        return max(-100, min(100, $trust));
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

        $base = (int) config('game.food.hunger_base_malus', 2);
        $step = (int) config('game.food.hunger_step', 1);
        $cap = (int) config('game.food.hunger_cap', 8);

        return -min($base + ($streak - 1) * $step, $cap);
    }

    /**
     * Read the current stored trust for a colony (-100..+100).
     */
    public function getTrust(int $colonyId): int
    {
        $row = DB::table('colony_resources')
            ->where('colony_id', $colonyId)
            ->where('resource_id', $this->resourceId())
            ->first();

        return $row ? (int) $row->amount : 0;
    }

    private function resourceId(): int
    {
        return (int) config('game.trust.resource_id', self::RESOURCE_ID);
    }

    // ── Multipliers ───────────────────────────────────────────────────────────

    /**
     * Return the production multiplier for the given trust value.
     */
    public function getProductionMultiplier(int $trust): float
    {
        return $this->lookupMultiplier($trust, 'production_multiplier');
    }

    /**
     * Return the AP multiplier for the given trust value.
     */
    public function getApMultiplier(int $trust): float
    {
        return $this->lookupMultiplier($trust, 'ap_multiplier');
    }

    /**
     * Return the display name of the trust band.
     */
    public function getBand(int $trust): string
    {
        if ($trust >= 61) {
            return __('trust.band_euphorisch');
        }
        if ($trust >= 21) {
            return __('trust.band_zufrieden');
        }
        if ($trust >= -20) {
            return __('trust.band_stabil');
        }
        if ($trust >= -61) {
            return __('trust.band_unruhig');
        }

        return __('trust.band_aufruhr');
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

        $cap = (int) config('game.trust.ships_cap', 30);

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
     * wins; different Sols add up. Security Hub mitigation applies to the delta before the profile.
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

        // SecurityHub (building_id=53) mitigates negative trust events by 25%.
        $mitigationPct = (float) config('buildings.securityHub.event_mitigation_pct', 0);
        $mitigate = $mitigationPct > 0 && DB::table('colony_buildings')
            ->where('colony_id', $colonyId)
            ->where('building_id', 53)
            ->where('level', '>', 0)
            ->exists();

        $lines = [];
        foreach ($byKey as $e) {
            $delta = $e['delta'];
            if ($mitigate && $delta < 0) {
                $delta = (int) round($delta * (1 - $mitigationPct));
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

    private function lookupMultiplier(int $trust, string $configKey): float
    {
        foreach (config("game.trust.{$configKey}", []) as $band) {
            if ($trust >= $band['min'] && $trust <= $band['max']) {
                return (float) $band['factor'];
            }
        }

        return 1.0;
    }
}
