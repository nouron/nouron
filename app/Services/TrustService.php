<?php

namespace App\Services;

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
    private function hungerPenalty(int $colonyId): int
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
     * Sum event contributions for the tick.
     * Multiple events of the same type do NOT stack — only the strongest (by absolute value) counts.
     */
    private function eventContribution(int $colonyId, int $tick): int
    {
        $cfg = config('game.trust.events', []);
        if (empty($cfg)) {
            return 0;
        }

        $events = DB::table('trust_events')
            ->where('colony_id', $colonyId)
            ->where('tick', $tick)
            ->pluck('event_type');

        // Group by type, keep strongest delta (by abs value)
        $byType = [];
        foreach ($events as $type) {
            $delta = $cfg[$type] ?? 0;
            if (! isset($byType[$type]) || abs($delta) > abs($byType[$type])) {
                $byType[$type] = $delta;
            }
        }

        // SecurityHub (building_id=53) mitigates negative trust events by 25%.
        $mitigationPct = (float) config('buildings.securityHub.event_mitigation_pct', 0);
        if ($mitigationPct > 0) {
            $hubActive = DB::table('colony_buildings')
                ->where('colony_id', $colonyId)
                ->where('building_id', 53)
                ->where('level', '>', 0)
                ->exists();
            if ($hubActive) {
                foreach ($byType as $type => $delta) {
                    if ($delta < 0) {
                        $byType[$type] = (int) round($delta * (1 - $mitigationPct));
                    }
                }
            }
        }

        return array_sum($byType);
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
