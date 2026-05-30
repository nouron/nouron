<?php

namespace App\Services;

use App\Services\TickService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * MerchantService — handles Traveling Merchant (Reisender Händler) visits.
 *
 * The merchant is a colony-level system event, completely separate from the
 * Bar/Cantina. It appears randomly after Sol 15–20 and then every 10–15 Sols,
 * stays for 2 Sols, and offers 3 special items for Credits.
 *
 * Item effects implemented in Phase 3:
 *   - repair_kit   → adds sp_amount to colony_buildings.status_points (capped at max_status_points)
 *                    for the building with the lowest relative condition
 *   - trust_boost  → adds trust_amount to colony_resources (resource_id = 12)
 *   - information  → sets colony_tiles.is_explored = 1 for all tiles of the colony
 *
 * Item effects deferred to Phase 4:
 *   - ap_flex / ap_targeted → marked sold, effect logged as TODO
 *   - credit_loan           → not yet offered (config placeholder)
 */
class MerchantService
{
    private const TRUST_RESOURCE_ID = 12;

    public function getActiveVisit(int $colonyId, int $currentTick): ?object
    {
        return DB::table('merchant_visits')
            ->where('colony_id', $colonyId)
            ->where('tick_start', '<=', $currentTick)
            ->where('tick_end', '>=', $currentTick)
            ->first();
    }

    public function spawnVisit(int $colonyId, int $currentTick): void
    {
        $cfg      = config('game.merchant', []);
        $duration = (int) ($cfg['duration_ticks'] ?? 2);
        $count    = (int) ($cfg['items_count'] ?? 3);
        $pool     = $cfg['items'] ?? [];

        if (empty($pool)) {
            return;
        }

        $visitId = DB::table('merchant_visits')->insertGetId([
            'colony_id'   => $colonyId,
            'tick_start'  => $currentTick,
            'tick_end'    => $currentTick + $duration - 1,
            'was_visited' => false,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        // Pick $count unique item types pseudo-randomly, seeded by colonyId + tick.
        $types   = array_keys($pool);
        $picked  = $this->pickItems($types, $count, $colonyId, $currentTick);

        foreach ($picked as $type) {
            $def = $pool[$type] ?? [];
            DB::table('merchant_items')->insert([
                'visit_id'     => $visitId,
                'item_type'    => $type,
                'label'        => $def['label'] ?? $type,
                'cost_credits' => (int) ($def['cost'] ?? 0),
                'payload'      => $this->buildPayload($type, $def),
                'sold'         => false,
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);
        }
    }

    public function shouldSpawn(int $colonyId, int $currentTick): bool
    {
        $cfg = config('game.merchant', []);

        $firstMin    = (int) ($cfg['first_appearance_min'] ?? 15);
        $intervalMin = (int) ($cfg['interval_min'] ?? 10);
        $intervalMax = (int) ($cfg['interval_max'] ?? 15);
        $intervalAvg = ($intervalMin + $intervalMax) / 2.0;

        // No spawn without a built Cantina (bar building_id=52, level > 0)
        $barBuilt = DB::table('colony_buildings')
            ->where('colony_id', $colonyId)
            ->where('building_id', 52)
            ->where('level', '>', 0)
            ->exists();

        if (!$barBuilt) {
            return false;
        }

        // Not yet past the earliest possible first appearance
        if ($currentTick < $firstMin) {
            return false;
        }

        // No spawn if a visit is currently active or scheduled in the future
        $existingVisit = DB::table('merchant_visits')
            ->where('colony_id', $colonyId)
            ->where('tick_end', '>=', $currentTick)
            ->exists();

        if ($existingVisit) {
            return false;
        }

        // Find when the last visit ended
        $lastEnd = DB::table('merchant_visits')
            ->where('colony_id', $colonyId)
            ->max('tick_end');

        if ($lastEnd !== null) {
            // Must wait at least interval_min Sols since last visit ended
            if (($currentTick - (int) $lastEnd) < $intervalMin) {
                return false;
            }
        }

        // Random chance: ~1/interval_avg per tick so visits are spread out naturally.
        // Deterministic seed per colony+tick so parallel processing is idempotent.
        $seed  = $colonyId * 1664525 + $currentTick * 1013904223;
        $hash  = abs($seed & 0x7FFFFFFF);
        $frac  = $hash / 0x7FFFFFFF; // 0.0 – 1.0

        return $frac < (1.0 / $intervalAvg);
    }

    public function buyItem(int $itemId, int $colonyId, int $userId): array
    {
        $item = DB::table('merchant_items')->where('id', $itemId)->first();

        if (!$item) {
            return ['ok' => false, 'error' => 'Item nicht gefunden.'];
        }

        if ($item->sold) {
            return ['ok' => false, 'error' => 'Dieses Item wurde bereits gekauft.'];
        }

        // Verify the visit is still active
        $tick  = app(TickService::class)->getTickCount();
        $visit = DB::table('merchant_visits')
            ->where('id', $item->visit_id)
            ->where('colony_id', $colonyId)
            ->where('tick_start', '<=', $tick)
            ->where('tick_end', '>=', $tick)
            ->first();

        if (!$visit) {
            return ['ok' => false, 'error' => 'Der Händler ist nicht mehr anwesend.'];
        }

        // Check credits
        $credits = (int) (DB::table('user_resources')
            ->where('user_id', $userId)
            ->value('credits') ?? 0);

        if ($credits < $item->cost_credits) {
            return ['ok' => false, 'error' => 'Nicht genug Credits.'];
        }

        // Deduct credits
        DB::table('user_resources')
            ->where('user_id', $userId)
            ->decrement('credits', $item->cost_credits);

        // Apply effect
        $this->applyItemEffect($item, $colonyId);

        // Mark sold
        DB::table('merchant_items')
            ->where('id', $itemId)
            ->update(['sold' => true, 'updated_at' => now()]);

        $newCredits = (int) (DB::table('user_resources')
            ->where('user_id', $userId)
            ->value('credits') ?? 0);

        return [
            'ok'      => true,
            'message' => 'Kauf erfolgreich.',
            'credits' => $newCredits,
        ];
    }

    public function getItemsForVisit(int $visitId): Collection
    {
        return DB::table('merchant_items')
            ->where('visit_id', $visitId)
            ->orderBy('id')
            ->get();
    }

    public function markVisited(int $visitId, int $colonyId): void
    {
        DB::table('merchant_visits')
            ->where('id', $visitId)
            ->where('colony_id', $colonyId)
            ->update(['was_visited' => true, 'updated_at' => now()]);
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    /**
     * Pick $count unique item types from $pool pseudo-randomly, seeded by colony + tick.
     *
     * @param  string[] $pool
     * @return string[]
     */
    private function pickItems(array $pool, int $count, int $colonyId, int $tick): array
    {
        $available = $pool;
        $count     = min($count, count($available));
        $picked    = [];

        for ($i = 0; $i < $count; $i++) {
            $seed   = abs(($colonyId * 997 + $tick * 31 + $i * 127) * 1664525 + 1013904223) & 0x7FFFFFFF;
            $idx    = $seed % count($available);
            $picked[] = array_splice($available, (int) $idx, 1)[0];
        }

        return $picked;
    }

    /**
     * Build the JSON payload for an item based on its type and config definition.
     */
    private function buildPayload(string $type, array $def): ?string
    {
        $data = match ($type) {
            'ap_flex'     => ['ap_type' => 'any',          'amount' => $def['ap_amount'] ?? 20],
            'ap_targeted' => ['ap_type' => 'construction', 'amount' => $def['ap_amount'] ?? 15],
            'repair_kit'  => ['sp_amount' => $def['sp_amount'] ?? 30],
            'trust_boost' => ['trust_amount' => $def['trust_amount'] ?? 15],
            'information' => [],
            default       => [],
        };

        return empty($data) ? null : json_encode($data);
    }

    /**
     * Apply the item's game effect.
     *
     * repair_kit   → heal the colony building with the lowest relative SP
     * trust_boost  → add trust to colony_resources (resource_id = 12)
     * information  → reveal all colony_tiles for this colony
     * ap_flex / ap_targeted → deferred to Phase 4, logged
     */
    private function applyItemEffect(object $item, int $colonyId): void
    {
        $payload = $item->payload ? json_decode($item->payload, true) : [];

        switch ($item->item_type) {
            case 'repair_kit':
                $spAmount = (int) ($payload['sp_amount'] ?? 30);
                $this->applyRepairKit($colonyId, $spAmount);
                break;

            case 'trust_boost':
                $amount = (int) ($payload['trust_amount'] ?? 15);
                DB::table('colony_resources')
                    ->where('colony_id', $colonyId)
                    ->where('resource_id', self::TRUST_RESOURCE_ID)
                    ->increment('amount', $amount);
                break;

            case 'information':
                DB::table('colony_tiles')
                    ->where('colony_id', $colonyId)
                    ->update(['is_explored' => true]);
                break;

            case 'ap_flex':
            case 'ap_targeted':
                // TODO Phase 4: integrate with PersonellService to credit AP directly.
                Log::info("MerchantService: AP item '{$item->item_type}' purchased for colony {$colonyId} — effect deferred to Phase 4.");
                break;

            default:
                Log::warning("MerchantService: unknown item_type '{$item->item_type}' — no effect applied.");
                break;
        }
    }

    /**
     * Heal the colony building with the lowest relative condition (SP / max_SP).
     * SP is capped at max_status_points.
     */
    private function applyRepairKit(int $colonyId, int $spAmount): void
    {
        // Find the building with the lowest condition ratio that is actually placed (level > 0)
        $target = DB::table('colony_buildings as cb')
            ->join('buildings as b', 'b.id', '=', 'cb.building_id')
            ->where('cb.colony_id', $colonyId)
            ->where('cb.level', '>', 0)
            ->select(
                'cb.building_id',
                'cb.instance_id',
                'cb.status_points',
                'b.max_status_points'
            )
            ->get()
            ->sortBy(function ($row) {
                $max = max(1, (int) $row->max_status_points);
                return (float) $row->status_points / $max;
            })
            ->first();

        if (!$target) {
            return; // No buildings to repair
        }

        $maxSP    = max(1, (int) $target->max_status_points);
        $newSP    = min($maxSP, (float) $target->status_points + $spAmount);

        DB::table('colony_buildings')
            ->where('colony_id', $colonyId)
            ->where('building_id', $target->building_id)
            ->where('instance_id', $target->instance_id)
            ->update(['status_points' => $newSP]);
    }
}
