<?php

namespace App\Services;

use App\Services\Techtree\PersonellService;
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
 * Item effects implemented in Phase 4:
 *   - ap_flex     → distributes AP across all advisor types with active advisors (PersonellService::creditAp)
 *   - ap_targeted → credits AP to the specific type stored in the item payload
 *
 * Item effects still deferred:
 *   - credit_loan — not yet offered (config placeholder)
 *
 * Corvan's Alltagsgeschäft (GDD §4b "Pfad-C-Hebel: von Regolith zu Credits", §12
 * Kanal 1 "Corvan wird die zentrale Handelsfigur der Cantina", Freigegeben
 * 2026-08-05): each visit also carries commodity offers — an Organika sell
 * channel plus an optional Credits→resource buy offer — persisted as bar_offers
 * rows with visit_id set (see generateCommodityOffers()). Reuses BarService's
 * existing accept/negotiate/AP-charge pipeline instead of a parallel one.
 */
class MerchantService
{
    private const TRUST_RESOURCE_ID = 12;

    private const RES_CREDITS = 1;

    public function __construct(
        private readonly PersonellService $personellService,
        private readonly BarService $barService,
        private readonly ResourcesService $resourcesService,
    ) {}

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
        $cfg = config('game.merchant', []);
        $duration = (int) ($cfg['duration_ticks'] ?? 2);
        $count = (int) ($cfg['items_count'] ?? 3);
        $pool = $cfg['items'] ?? [];

        if (empty($pool)) {
            return;
        }

        $visitId = DB::table('merchant_visits')->insertGetId([
            'colony_id' => $colonyId,
            'tick_start' => $currentTick,
            'tick_end' => $currentTick + $duration - 1,
            'was_visited' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Pick $count unique item types pseudo-randomly, seeded by colonyId + tick.
        $types = array_keys($pool);
        $picked = $this->pickItems($types, $count, $colonyId, $currentTick);

        foreach ($picked as $type) {
            $def = $pool[$type] ?? [];
            DB::table('merchant_items')->insert([
                'visit_id' => $visitId,
                'item_type' => $type,
                'label' => $def['label'] ?? $type,
                'cost_credits' => (int) ($def['cost'] ?? 0),
                'payload' => $this->buildPayload($type, $def),
                'sold' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->generateCommodityOffers($colonyId, $visitId, $currentTick, $currentTick + $duration - 1);
    }

    /**
     * Corvan's Alltagsgeschäft (GDD §4b/§12): persisted as bar_offers rows with
     * visit_id set, valid for the whole visit (expires_tick = tick_end + 1, matching
     * the existing "expires_tick > tick" active predicate).
     *
     *   - Sell lots: Organika→Credits, 2–3 lots (sell_lot_count_min/max) sized
     *     sell_lot_size_min–max each. Generated only while the running total stays
     *     above sell_reserve_multiplier × ResourcesService::foodNeed() — the
     *     reserve floor also re-checked live at accept time in
     *     BarService::acceptOffer(), since stock can drop between generation and
     *     the player accepting a later lot.
     *   - Buy offer: Credits→resource via BarService::buildCorvanBuyOffer(). No
     *     barter fallback for Corvan — if unaffordable, simply omitted.
     *
     * No-op (and no error) when game.merchant.commodity is not configured.
     */
    private function generateCommodityOffers(int $colonyId, int $visitId, int $currentTick, int $tickEnd): void
    {
        $cfg = config('game.merchant.commodity', []);

        if (empty($cfg)) {
            return;
        }

        $expiresTick = $tickEnd + 1;
        $sellResId = (int) ($cfg['sell_resource_id'] ?? 5);
        $pricePerUnit = (int) ($cfg['sell_price_per_unit'] ?? 35);
        $lotCountMin = (int) ($cfg['sell_lot_count_min'] ?? 2);
        $lotCountMax = (int) ($cfg['sell_lot_count_max'] ?? 3);
        $lotSizeMin = (int) ($cfg['sell_lot_size_min'] ?? 15);
        $lotSizeMax = (int) ($cfg['sell_lot_size_max'] ?? 25);
        $reserveMultiplier = (int) ($cfg['sell_reserve_multiplier'] ?? 2);

        $stock = (int) (DB::table('colony_resources')
            ->where('colony_id', $colonyId)
            ->where('resource_id', $sellResId)
            ->value('amount') ?? 0);
        $reserve = $reserveMultiplier * $this->resourcesService->foodNeed($colonyId);
        $available = max(0, $stock - $reserve);

        $lotCount = $this->pseudoRand($colonyId * 331 + $currentTick * 71, $lotCountMin, $lotCountMax);

        for ($i = 0; $i < $lotCount; $i++) {
            $size = $this->pseudoRand($colonyId * 4441 + $currentTick * 211 + $i * 17, $lotSizeMin, $lotSizeMax);

            if ($size > $available) {
                // Reserve floor reached — stop generating further lots this visit
                // rather than shrinking below sell_lot_size_min (§4b: a lot must
                // stay within the documented 15–25 range, not be squeezed thinner).
                break;
            }

            $available -= $size;

            DB::table('bar_offers')->insert([
                'colony_id' => $colonyId,
                'visit_id' => $visitId,
                'give_resource_id' => $sellResId,
                'give_amount' => $size,
                'get_resource_id' => self::RES_CREDITS,
                'get_amount' => $size * $pricePerUnit,
                'expires_tick' => $expiresTick,
                'is_accepted' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $traderRank = $this->barService->traderRank($colonyId);
        $userId = DB::table('v_glx_colonies')->where('id', $colonyId)->value('user_id');
        $credits = (int) (DB::table('user_resources')->where('user_id', $userId)->value('credits') ?? 0);
        $buySeed = $colonyId * 8887 + $currentTick * 349;
        $buyOffer = $this->barService->buildCorvanBuyOffer($buySeed, $traderRank, $credits);

        if ($buyOffer !== null) {
            [$giveResId, $giveAmount, $getResId, $getAmount] = $buyOffer;

            DB::table('bar_offers')->insert([
                'colony_id' => $colonyId,
                'visit_id' => $visitId,
                'give_resource_id' => $giveResId,
                'give_amount' => $giveAmount,
                'get_resource_id' => $getResId,
                'get_amount' => $getAmount,
                'expires_tick' => $expiresTick,
                'is_accepted' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function pseudoRand(int $seed, int $min, int $max): int
    {
        if ($min >= $max) {
            return $min;
        }
        $hash = abs(($seed * 1664525 + 1013904223) & 0x7FFFFFFF);

        return $min + ($hash % ($max - $min + 1));
    }

    public function shouldSpawn(int $colonyId, int $currentTick): bool
    {
        $cfg = config('game.merchant', []);

        $firstMin = (int) ($cfg['first_appearance_min'] ?? 15);
        $firstMax = (int) ($cfg['first_appearance_max'] ?? 20);
        $intervalMin = (int) ($cfg['interval_min'] ?? 10);
        $intervalMax = (int) ($cfg['interval_max'] ?? 15);

        // No spawn without a built Cantina (bar building_id=52, level > 0)
        $barBuilt = DB::table('colony_buildings')
            ->where('colony_id', $colonyId)
            ->where('building_id', 52)
            ->where('level', '>', 0)
            ->exists();

        if (! $barBuilt) {
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

        if ($lastEnd === null) {
            // First appearance: spawn exactly on a deterministic tick within
            // [first_appearance_min, first_appearance_max] — not an independent
            // per-tick probability roll, which can (and, at the tighter Direction-1
            // interval, reliably does) push the effective gap well past the max.
            $targetTick = $this->deterministicTarget($colonyId, -1, $firstMin, $firstMax);

            return $currentTick >= $targetTick;
        }

        $gap = $currentTick - (int) $lastEnd;

        // Must wait at least interval_min Sols since last visit ended
        if ($gap < $intervalMin) {
            return false;
        }

        $targetGap = $this->deterministicTarget($colonyId, (int) $lastEnd, $intervalMin, $intervalMax);

        return $gap >= $targetGap;
    }

    /**
     * Deterministic pseudo-random integer in [$min, $max], seeded by colony + anchor
     * (the previous visit's tick_end, or -1 for "no prior visit"). Same seed inputs →
     * same output, so parallel tick processing stays idempotent — see shouldSpawn().
     */
    private function deterministicTarget(int $colonyId, int $anchor, int $min, int $max): int
    {
        if ($min >= $max) {
            return $min;
        }

        $seed = abs(($colonyId * 1664525 + ($anchor + 1) * 1013904223) & 0x7FFFFFFF);

        return $min + ($seed % ($max - $min + 1));
    }

    public function buyItem(int $itemId, int $colonyId, int $userId): array
    {
        $item = DB::table('merchant_items')->where('id', $itemId)->first();

        if (! $item) {
            return ['ok' => false, 'error' => 'Item nicht gefunden.'];
        }

        if ($item->sold) {
            return ['ok' => false, 'error' => 'Dieses Item wurde bereits gekauft.'];
        }

        // Verify the visit is still active
        $tick = app(TickService::class)->getTickCount();
        $visit = DB::table('merchant_visits')
            ->where('id', $item->visit_id)
            ->where('colony_id', $colonyId)
            ->where('tick_start', '<=', $tick)
            ->where('tick_end', '>=', $tick)
            ->first();

        if (! $visit) {
            return ['ok' => false, 'error' => 'Der Händler ist nicht mehr anwesend.'];
        }

        // Check credits
        $credits = (int) (DB::table('user_resources')
            ->where('user_id', $userId)
            ->value('credits') ?? 0);

        if ($credits < $item->cost_credits) {
            return ['ok' => false, 'error' => 'Nicht genug Credits.'];
        }

        // Deduct credits, apply effect and mark sold atomically.
        DB::transaction(function () use ($item, $itemId, $colonyId, $userId): void {
            DB::table('user_resources')
                ->where('user_id', $userId)
                ->decrement('credits', $item->cost_credits);

            $this->applyItemEffect($item, $colonyId);

            DB::table('merchant_items')
                ->where('id', $itemId)
                ->update(['sold' => true, 'updated_at' => now()]);
        });

        Log::info('merchant_purchase', [
            'colony_id' => $colonyId,
            'user_id' => $userId,
            'item_id' => $itemId,
            'item_type' => $item->item_type,
            'cost_credits' => $item->cost_credits,
        ]);

        $newCredits = (int) (DB::table('user_resources')
            ->where('user_id', $userId)
            ->value('credits') ?? 0);

        return [
            'ok' => true,
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
     * @param  string[]  $pool
     * @return string[]
     */
    private function pickItems(array $pool, int $count, int $colonyId, int $tick): array
    {
        $available = $pool;
        $count = min($count, count($available));
        $picked = [];

        for ($i = 0; $i < $count; $i++) {
            $seed = abs(($colonyId * 997 + $tick * 31 + $i * 127) * 1664525 + 1013904223) & 0x7FFFFFFF;
            $idx = $seed % count($available);
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
            'ap_flex' => ['ap_type' => 'any',          'amount' => $def['ap_amount'] ?? 20],
            'ap_targeted' => ['ap_type' => 'construction', 'amount' => $def['ap_amount'] ?? 15],
            'repair_kit' => ['sp_amount' => $def['sp_amount'] ?? 30],
            'trust_boost' => ['trust_amount' => $def['trust_amount'] ?? 15],
            'information' => [],
            default => [],
        };

        return empty($data) ? null : json_encode($data);
    }

    /**
     * Apply the item's game effect.
     *
     * repair_kit   → heal the colony building with the lowest relative SP
     * trust_boost  → add trust to colony_resources (resource_id = 12)
     * information  → reveal all colony_tiles for this colony
     * ap_flex      → distribute AP across advisor types via PersonellService::creditAp
     * ap_targeted  → credit AP to a specific advisor type via PersonellService::creditAp
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
                // ap_flex: distribute AP across all advisor types that have active advisors.
                $apAmount = (int) ($payload['amount'] ?? 20);
                $this->personellService->creditAp($colonyId, 'any', $apAmount);
                Log::info("MerchantService: ap_flex applied — {$apAmount} AP distributed across active advisors on colony {$colonyId}.");
                break;

            case 'ap_targeted':
                // ap_targeted: credit AP to the specific type stored in the payload.
                $apAmount = (int) ($payload['amount'] ?? 15);
                $apType = (string) ($payload['ap_type'] ?? 'construction');
                $this->personellService->creditAp($colonyId, $apType, $apAmount);
                Log::info("MerchantService: ap_targeted applied — {$apAmount} AP credited to '{$apType}' on colony {$colonyId}.");
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

        if (! $target) {
            return; // No buildings to repair
        }

        $maxSP = max(1, (int) $target->max_status_points);
        $newSP = min($maxSP, (float) $target->status_points + $spAmount);

        DB::table('colony_buildings')
            ->where('colony_id', $colonyId)
            ->where('building_id', $target->building_id)
            ->where('instance_id', $target->instance_id)
            ->update(['status_points' => $newSP]);
    }
}
