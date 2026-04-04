<?php

namespace App\Services;

use App\Models\Colony;
use App\Services\TickService;
use Illuminate\Support\Facades\DB;

/**
 * MoralService — calculates and stores colony moral.
 *
 * Moral ranges from -100 to +100 (neutral = 0, start = 0).
 * It is recalculated every tick from static factors (buildings, researches, ships)
 * and one-shot events (stored in moral_events for the current tick).
 *
 * Stored in colony_resources.amount WHERE resource_id = 12 (res_moral).
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
class MoralService
{
    public const RESOURCE_ID = 12;

    public function __construct(
        private readonly TickService $tickService,
    ) {}

    // ── Calculation ───────────────────────────────────────────────────────────

    /**
     * Calculate moral for a colony and persist it in colony_resources.
     * Called by GameTick step 8b (after resource generation).
     */
    public function calculateAndStore(Colony $colony, int $tick): int
    {
        $moral = $this->calculateMoral($colony->id, $tick);

        DB::table('colony_resources')->updateOrInsert(
            ['colony_id' => $colony->id, 'resource_id' => self::RESOURCE_ID],
            ['amount' => $moral]
        );

        return $moral;
    }

    /**
     * Calculate raw moral value for a colony at the given tick.
     * Does NOT persist the result.
     */
    public function calculateMoral(int $colonyId, int $tick): int
    {
        $moral = 0;
        $moral += $this->buildingContribution($colonyId);
        $moral += $this->researchContribution($colonyId);
        $moral += $this->shipContribution($colonyId);
        $moral += $this->eventContribution($colonyId, $tick);

        return max(-100, min(100, $moral));
    }

    /**
     * Read the current stored moral for a colony (-100..+100).
     */
    public function getMoral(int $colonyId): int
    {
        $row = DB::table('colony_resources')
            ->where('colony_id', $colonyId)
            ->where('resource_id', self::RESOURCE_ID)
            ->first();

        return $row ? (int) $row->amount : 0;
    }

    // ── Multipliers ───────────────────────────────────────────────────────────

    /**
     * Return the production multiplier for the given moral value.
     */
    public function getProductionMultiplier(int $moral): float
    {
        return $this->lookupMultiplier($moral, 'production_multiplier');
    }

    /**
     * Return the AP multiplier for the given moral value.
     */
    public function getApMultiplier(int $moral): float
    {
        return $this->lookupMultiplier($moral, 'ap_multiplier');
    }

    /**
     * Return the display name of the moral band.
     */
    public function getBand(int $moral): string
    {
        if ($moral >= 61)  return __('moral.band_euphorisch');
        if ($moral >= 21)  return __('moral.band_zufrieden');
        if ($moral >= -20) return __('moral.band_stabil');
        if ($moral >= -61) return __('moral.band_unruhig');
        return __('moral.band_aufruhr');
    }

    // ── Events ────────────────────────────────────────────────────────────────

    /**
     * Fire a moral event for a colony.
     *
     * Events are one-shot: they contribute to exactly one tick's moral calculation.
     * For events fired outside the tick cycle (e.g. from controllers), use the
     * next tick so they are picked up at the next recalculation.
     *
     * @param  int    $colonyId
     * @param  string $eventType  Key from config('game.moral.events')
     * @param  int|null $tick     Defaults to current tick + 1 (for out-of-tick callers).
     *                            Pass current tick when called from within GameTick.
     */
    public function fireEvent(int $colonyId, string $eventType, ?int $tick = null): void
    {
        if (!array_key_exists($eventType, config('game.moral.events', []))) {
            return; // unknown event type — silently ignore
        }

        $tick ??= $this->tickService->getTickCount() + 1;

        DB::table('moral_events')->insert([
            'colony_id'  => $colonyId,
            'tick'       => $tick,
            'event_type' => $eventType,
        ]);
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function buildingContribution(int $colonyId): int
    {
        // Build [id => moral_per_lv] map from config/buildings.php
        $cfg = collect(config('buildings', []))->pluck('moral_per_lv', 'id')->filter()->toArray();
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
        // Build [id => moral_per_lv] map from config/techs.php
        $cfg = collect(config('techs', []))->pluck('moral_per_lv', 'id')->filter()->toArray();
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
        // Build [id => moral_per_unit] map from config/ships.php
        $cfg = collect(config('ships', []))->pluck('moral_per_unit', 'id')->filter()->toArray();
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

        $cap = (int) config('game.moral.ships_cap', 30);
        return max(-$cap, min($cap, $sum));
    }

    /**
     * Sum event contributions for the tick.
     * Multiple events of the same type do NOT stack — only the strongest (by absolute value) counts.
     */
    private function eventContribution(int $colonyId, int $tick): int
    {
        $cfg = config('game.moral.events', []);
        if (empty($cfg)) {
            return 0;
        }

        $events = DB::table('moral_events')
            ->where('colony_id', $colonyId)
            ->where('tick', $tick)
            ->pluck('event_type');

        // Group by type, keep strongest delta (by abs value)
        $byType = [];
        foreach ($events as $type) {
            $delta = $cfg[$type] ?? 0;
            if (!isset($byType[$type]) || abs($delta) > abs($byType[$type])) {
                $byType[$type] = $delta;
            }
        }

        return array_sum($byType);
    }

    private function lookupMultiplier(int $moral, string $configKey): float
    {
        foreach (config("game.moral.{$configKey}", []) as $band) {
            if ($moral >= $band['min'] && $moral <= $band['max']) {
                return (float) $band['factor'];
            }
        }
        return 1.0;
    }
}
