<?php

namespace App\Services;

use App\Enums\BuildingId;
use Illuminate\Support\Facades\DB;

/**
 * CorporateContactService — Orin (corporate_rep) — Weg A for the Harvester second
 * instance (GDD §4c "Harvester-Zweitinstanz: Bezugsquelle", freigegeben 2026-08-05).
 *
 * A dedicated, small spawn-check modelled after MerchantService::shouldSpawn(), but
 * with its own config namespace (config('game.corporate_contact')) and its own
 * service — deliberately NOT part of MerchantService (Corvan Ashe's inventory, GDD
 * explicitly rules out merging the two) and NOT part of the generic BarService guest
 * rotation (built for frequent, small trades, not a single rare high-value deal).
 *
 * Stateless by design: no visits table. getActiveOffer() is a pure, deterministic
 * function of (colonyId, tick) — re-derived on every read AND on purchase, so the
 * display path and the buy path can never drift apart from each other. Two-level
 * roll: level 1 = does Orin appear this Sol at all (rare); level 2 = does the
 * appearance carry the harvester deal, conditional on level 1.
 */
class CorporateContactService
{
    public function __construct(
        private readonly HarvesterEntitlementService $harvesterEntitlementService,
        private readonly TradingPostService $tradingPostService,
    ) {}

    /**
     * Returns the active harvester offer for this colony/user/tick, or null when
     * Orin isn't offering it (gate not met, already at max instances, the user
     * already holds an entitlement via any path, or the roll misses).
     *
     * @return array{price: int}|null
     */
    public function getActiveOffer(int $colonyId, int $userId, int $tick): ?array
    {
        if (! $this->gatesSatisfied($colonyId, $userId)) {
            return null;
        }

        if (! $this->appearanceRoll($colonyId, $tick)) {
            return null;
        }

        if (! $this->offerRoll($colonyId, $tick)) {
            return null;
        }

        $price = $this->priceRoll($colonyId, $tick);

        // Handelsposten-Kanal-Rabatt (Design-Spec 2026-08-23) — Stufe 3 schaltet
        // den Nexus/Corporate-Contact-Kanal frei.
        $discount = $this->tradingPostService->discountFor($colonyId, 'corporate_contact');
        if ($discount > 0.0) {
            $price = (int) max(1, round($price * (1 - $discount)));
        }

        return ['price' => $price];
    }

    /**
     * Purchase Orin's harvester offer. Re-derives the offer server-side rather than
     * trusting a client-supplied price — the display path and the buy path share the
     * exact same computation, so they cannot disagree.
     *
     * @return array{ok: bool, error?: string, price?: int}
     */
    public function buyHarvesterOffer(int $colonyId, int $userId, int $tick): array
    {
        $offer = $this->getActiveOffer($colonyId, $userId, $tick);
        if ($offer === null) {
            return ['ok' => false, 'error' => 'corporate_contact_offer_unavailable'];
        }

        $credits = (int) (DB::table('user_resources')->where('user_id', $userId)->value('credits') ?? 0);
        if ($credits < $offer['price']) {
            return ['ok' => false, 'error' => 'insufficient_credits'];
        }

        DB::transaction(function () use ($userId, $offer): void {
            DB::table('user_resources')->where('user_id', $userId)->decrement('credits', $offer['price']);
            $this->harvesterEntitlementService->grantPurchase($userId);
        });

        return ['ok' => true, 'price' => $offer['price']];
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    /**
     * CC-Lv3 gate (unchanged, GDD §4c) plus the instance-count gate and the
     * entitlement gate: the offer must not appear at all when the colony couldn't
     * buy it anyway, OR when the user already holds an earned-but-unplaced
     * entitlement via any path (Weg A/B must not stack — instance_count alone
     * doesn't catch an earned-but-not-yet-placed entitlement).
     */
    private function gatesSatisfied(int $colonyId, int $userId): bool
    {
        if ($this->harvesterEntitlementService->hasEntitlement($userId)) {
            return false;
        }

        $requiredCcLevel = (int) config('game.harvester.second_instance_cc_level', 3);
        $ccLevel = (int) DB::table('colony_buildings')
            ->where('colony_id', $colonyId)
            ->where('building_id', BuildingId::CommandCenter->value)
            ->value('level');

        if ($ccLevel < $requiredCcLevel) {
            return false;
        }

        $instanceCount = DB::table('colony_buildings')
            ->where('colony_id', $colonyId)
            ->where('building_id', BuildingId::Harvester->value)
            ->whereNotNull('tile_x')
            ->count();
        $maxInstances = (int) (collect(config('buildings'))->firstWhere('id', BuildingId::Harvester->value)['max_instances'] ?? 2);

        return $instanceCount < $maxInstances;
    }

    /** Level 1: does Orin appear at all this Sol (rare — playtest candidate interval). */
    private function appearanceRoll(int $colonyId, int $tick): bool
    {
        $cfg = config('game.corporate_contact', []);
        $intervalMin = (int) ($cfg['appearance_interval_min'] ?? 15);
        $intervalMax = (int) ($cfg['appearance_interval_max'] ?? 25);
        $intervalAvg = ($intervalMin + $intervalMax) / 2.0;

        return $this->frac($colonyId, $tick, 2246822519, 3266489917) < (1.0 / $intervalAvg);
    }

    /** Level 2: given an appearance, does it carry the harvester deal. */
    private function offerRoll(int $colonyId, int $tick): bool
    {
        $offerChance = (float) (config('game.corporate_contact.offer_chance') ?? 0.30);

        return $this->frac($colonyId, $tick, 374761393, 668265263) < $offerChance;
    }

    /** Deterministic price within [price_min, price_max], seeded independently of both rolls. */
    private function priceRoll(int $colonyId, int $tick): int
    {
        $min = (int) (config('game.corporate_contact.price_min') ?? 400);
        $max = (int) (config('game.corporate_contact.price_max') ?? 800);
        $span = max(1, $max - $min + 1);

        $offset = (int) floor($this->frac($colonyId, $tick, 2654435761, 40503) * $span);

        return min($max, $min + $offset);
    }

    /**
     * Deterministic 0.0-1.0 fraction from (colonyId, tick), seeded by the given
     * multiplier pair so appearance/offer/price rolls don't correlate with each other
     * or with MerchantService::shouldSpawn's own seed.
     */
    private function frac(int $colonyId, int $tick, int $mult1, int $mult2): float
    {
        $seed = abs($colonyId * $mult1 + $tick * $mult2) % 0x7FFFFFFF;

        return $seed / 0x7FFFFFFF;
    }
}
