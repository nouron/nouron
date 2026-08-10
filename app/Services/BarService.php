<?php

namespace App\Services;

use App\Models\BarOffer;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BarService
{
    private const BAR_BUILDING_ID = 52;

    private const TRADER_ADVISOR_ID = 92; // personell.id for 'trader'

    private const RES_CREDITS = 1;

    private const TRADEABLE = [3, 4, 5]; // regolith, compounds, organics

    public function __construct(
        private readonly ResourcesService $resourcesService,
        private readonly AdvisorService $advisorService,
    ) {}

    public function generateOffersForColony(int $colonyId, int $tick): void
    {
        // Bar must exist and be level ≥ 1
        $barLevel = (int) DB::table('colony_buildings')
            ->where('colony_id', $colonyId)
            ->where('building_id', self::BAR_BUILDING_ID)
            ->value('level');

        if ($barLevel < 1) {
            return;
        }

        // Expire old offers
        DB::table('bar_offers')
            ->where('colony_id', $colonyId)
            ->where('expires_tick', '<=', $tick)
            ->where('is_accepted', false)
            ->delete();

        // Trader advisor rank (0 if none assigned)
        $traderRank = $this->traderRank($colonyId);

        [$minGuests, $maxGuests] = config("game.bar.guest_count.{$traderRank}", [0, 1]);
        $guestCount = $this->pseudoRand($colonyId * 997 + $tick * 31, $minGuests, $maxGuests);

        if ($guestCount < 1) {
            return;
        }

        // Cap to level_max_concurrent — don't exceed simultaneous active guest slots.
        // Corvan's commodity offers (bar_offers.visit_id set, GDD §12 Kanal 1) have
        // their own budget from game.merchant.commodity and must not consume — or be
        // consumed by — the generic guest rotation's slot count.
        $levelMaxConcurrent = config('game.bar.level_max_concurrent', []);
        $maxConcurrent = $levelMaxConcurrent[$barLevel] ?? 2;
        $activeCount = DB::table('bar_offers')
            ->where('colony_id', $colonyId)
            ->where('expires_tick', '>', $tick)
            ->where('is_accepted', false)
            ->whereNull('visit_id')
            ->count();
        $guestCount = min($guestCount, max(0, $maxConcurrent - $activeCount));

        if ($guestCount < 1) {
            return;
        }

        $levelDurations = config('game.bar.level_offer_duration', []);
        $duration = $levelDurations[$barLevel] ?? (int) config('game.bar.offer_duration', 2);
        $expiresTick = $tick + $duration;
        $discount = (float) config("game.bar.trader_discount.{$traderRank}", 0.0);
        $basePrices = config('game.bar.base_prices', [3 => 30, 4 => 60, 5 => 50]);

        for ($i = 0; $i < $guestCount; $i++) {
            $seed = $colonyId * 1009 + $tick * 127 + $i * 37;
            [$giveResId, $giveAmount, $getResId, $getAmount] =
                $this->buildBarterOffer($seed, $basePrices, $discount);

            BarOffer::create([
                'colony_id' => $colonyId,
                'give_resource_id' => $giveResId,
                'give_amount' => $giveAmount,
                'get_resource_id' => $getResId,
                'get_amount' => $getAmount,
                'expires_tick' => $expiresTick,
                'is_accepted' => false,
            ]);
        }
    }

    public function getActiveOffers(int $colonyId, int $tick): Collection
    {
        return BarOffer::where('colony_id', $colonyId)
            ->where('expires_tick', '>', $tick)
            ->where('is_accepted', false)
            ->orderBy('id')
            ->get();
    }

    /** Whether an assigned, available (not on a mission) Konsul (trader advisor) exists for this colony. */
    public function hasAvailableConsul(int $colonyId): bool
    {
        return DB::table('advisors')
            ->where('colony_id', $colonyId)
            ->where('personell_id', self::TRADER_ADVISOR_ID)
            ->whereNull('unavailable_until_tick')
            ->exists();
    }

    public function acceptOffer(int $colonyId, int $offerId, int $userId, int $currentTick): array
    {
        $offer = BarOffer::where('id', $offerId)
            ->where('colony_id', $colonyId)
            ->first();

        if (! $offer) {
            return ['ok' => false, 'error' => __('colony.bar_offer_not_found')];
        }
        if ($offer->is_accepted) {
            return ['ok' => false, 'error' => __('colony.bar_offer_already_accepted')];
        }

        if ($offer->expires_tick <= $currentTick) {
            return ['ok' => false, 'error' => __('colony.bar_offer_expired')];
        }

        // Economy-AP check — waived when the offer was already negotiated
        // (ap_cost_negotiate was already paid during that step; Annehmen here is
        // just confirming the improved terms, not a second priced action).
        $apCost = $offer->is_negotiated ? 0 : (int) config('game.bar.ap_cost_accept', 1);
        if ($apCost > 0 && ! config('game.bypass.ap_checks')) {
            $availableAp = $this->advisorService->getAvailableActionPoints($colonyId);
            if ($availableAp < $apCost) {
                return ['ok' => false, 'error' => __('colony.bar_offer_insufficient_ap')];
            }
        }

        // Check player can afford the give side
        $giveBalance = $this->getResourceBalance($colonyId, $userId, $offer->give_resource_id);
        if ($giveBalance < $offer->give_amount) {
            return ['ok' => false, 'error' => __('colony.bar_offer_insufficient_resources')];
        }

        // Reserve floor (GDD §4b) — only for Corvan's Organika sell offers
        // (visit_id set). Re-checked here, not just at generation, because stock
        // can drop between generation and accept (an earlier lot accepted in the
        // same visit, or ongoing food consumption) — see generateCommodityOffers().
        if ($offer->visit_id !== null) {
            $sellResId = (int) config('game.merchant.commodity.sell_resource_id', 5);
            if ((int) $offer->give_resource_id === $sellResId) {
                $reserveMultiplier = (int) config('game.merchant.commodity.sell_reserve_multiplier', 2);
                $reserve = $reserveMultiplier * $this->resourcesService->foodNeed($colonyId);
                if (($giveBalance - $offer->give_amount) < $reserve) {
                    return ['ok' => false, 'error' => __('colony.bar_offer_reserve_floor')];
                }
            }
        }

        // Execute trade atomically — partial transfer must not persist
        DB::transaction(function () use ($offer, $colonyId, $apCost): void {
            $this->resourcesService->decreaseAmount($colonyId, $offer->give_resource_id, $offer->give_amount);
            $this->resourcesService->increaseAmount($colonyId, $offer->get_resource_id, $offer->get_amount);
            $offer->is_accepted = true;
            $offer->save();
            if ($apCost > 0) {
                $this->advisorService->lockActionPoints($colonyId, $apCost, self::TRADER_ADVISOR_ID);
            }
        });

        Log::info('bar_trade', [
            'colony_id' => $colonyId,
            'offer_id' => $offerId,
            'give_resource_id' => $offer->give_resource_id,
            'give_amount' => $offer->give_amount,
            'get_resource_id' => $offer->get_resource_id,
            'get_amount' => $offer->get_amount,
        ]);

        return [
            'ok' => true,
            'give_resource_id' => $offer->give_resource_id,
            'give_amount' => $offer->give_amount,
            'get_resource_id' => $offer->get_resource_id,
            'get_amount' => $offer->get_amount,
        ];
    }

    /**
     * Cantina-Verhandlung (Risiko-Handel, GDD §12 Kanal 1) — alternative resolution
     * path for a bar offer. Requires an assigned, available Konsul (trader advisor).
     * Costs more Economy-AP than acceptOffer(); two-step outcome:
     *   - Success: the offer's terms are improved in place (rank-scaled bonus) and
     *     flagged is_negotiated — the trade does NOT execute yet, the player still
     *     confirms with acceptOffer() (which waives its AP cost for a negotiated
     *     offer, since ap_cost_negotiate already covered it).
     *   - Failure: the offer is lost entirely (deleted) — no fallback to accept.
     * AP is spent either way.
     */
    public function negotiateOffer(int $colonyId, int $offerId, int $userId, int $currentTick): array
    {
        $offer = BarOffer::where('id', $offerId)
            ->where('colony_id', $colonyId)
            ->first();

        if (! $offer) {
            return ['ok' => false, 'error' => __('colony.bar_offer_not_found')];
        }
        if ($offer->is_accepted) {
            return ['ok' => false, 'error' => __('colony.bar_offer_already_accepted')];
        }
        if ($offer->is_negotiated) {
            return ['ok' => false, 'error' => __('colony.bar_offer_already_negotiated')];
        }
        if ($offer->expires_tick <= $currentTick) {
            return ['ok' => false, 'error' => __('colony.bar_offer_expired')];
        }

        $traderRank = $this->traderRank($colonyId);

        if ($traderRank < 1) {
            return ['ok' => false, 'error' => __('colony.bar_offer_no_consul')];
        }

        $apCost = (int) config('game.bar.ap_cost_negotiate', 3);
        if ($apCost > 0 && ! config('game.bypass.ap_checks')) {
            $availableAp = $this->advisorService->getAvailableActionPoints($colonyId);
            if ($availableAp < $apCost) {
                return ['ok' => false, 'error' => __('colony.bar_offer_insufficient_ap')];
            }
        }

        $giveBalance = $this->getResourceBalance($colonyId, $userId, $offer->give_resource_id);
        if ($giveBalance < $offer->give_amount) {
            return ['ok' => false, 'error' => __('colony.bar_offer_insufficient_resources')];
        }

        $successChance = (float) config("game.bar.negotiate_success_chance.{$traderRank}", 0.0);
        $roll = $this->pseudoRand($offer->id * 7919 + $currentTick * 131, 0, 99);
        $success = $roll < (int) round($successChance * 100);

        return DB::transaction(function () use ($offer, $colonyId, $apCost, $traderRank, $success): array {
            if ($apCost > 0) {
                $this->advisorService->lockActionPoints($colonyId, $apCost, self::TRADER_ADVISOR_ID);
            }

            if (! $success) {
                $offer->delete();

                Log::info('bar_trade_negotiate_failed', [
                    'colony_id' => $colonyId,
                    'offer_id' => $offer->id,
                ]);

                return ['ok' => true, 'success' => false];
            }

            $bonus = (float) config("game.bar.negotiate_bonus.{$traderRank}", 0.0);
            $isCreditsOffer = $offer->give_resource_id === self::RES_CREDITS;
            $giveAmount = $isCreditsOffer
                ? (int) max(1, round($offer->give_amount * (1 - $bonus)))
                : $offer->give_amount;
            $getAmount = $isCreditsOffer
                ? $offer->get_amount
                : (int) max(1, round($offer->get_amount * (1 + $bonus)));

            // Improve the offer's terms in place — the trade itself executes when
            // the player subsequently confirms via acceptOffer().
            $offer->give_amount = $giveAmount;
            $offer->get_amount = $getAmount;
            $offer->is_negotiated = true;
            $offer->save();

            Log::info('bar_trade_negotiate_success', [
                'colony_id' => $colonyId,
                'offer_id' => $offer->id,
                'give_resource_id' => $offer->give_resource_id,
                'give_amount' => $giveAmount,
                'get_resource_id' => $offer->get_resource_id,
                'get_amount' => $getAmount,
            ]);

            return [
                'ok' => true,
                'success' => true,
                'give_resource_id' => $offer->give_resource_id,
                'give_amount' => $giveAmount,
                'get_resource_id' => $offer->get_resource_id,
                'get_amount' => $getAmount,
            ];
        });
    }

    private function getResourceBalance(int $colonyId, int $userId, int $resId): int
    {
        if ($resId === self::RES_CREDITS) {
            return (int) (DB::table('user_resources')
                ->where('user_id', $userId)
                ->value('credits') ?? 0);
        }

        return (int) (DB::table('colony_resources')
            ->where('colony_id', $colonyId)
            ->where('resource_id', $resId)
            ->value('amount') ?? 0);
    }

    /**
     * Build a Credits→resource "buy" offer for Corvan's Alltagsgeschäft (GDD §12
     * Kanal 1 "Corvan wird die zentrale Handelsfigur der Cantina"). This replaces
     * the generic-guest credits offer type removed from generateOffersForColony() —
     * the anonymous guest rotation no longer trades Credits at all.
     *
     * Unlike the old generic-guest type, there is no barter fallback: Corvan
     * doesn't barter. If even 1 unit is unaffordable at the given credits balance,
     * this returns null and the caller simply omits the buy offer for that visit —
     * the sell lots (Organika→Credits, see §4b) still stand on their own.
     *
     * @return array{0:int,1:int,2:int,3:int}|null [give_resource_id, give_amount, get_resource_id, get_amount]
     */
    public function buildCorvanBuyOffer(int $seed, int $traderRank, int $credits): ?array
    {
        $basePrices = config('game.bar.base_prices', [3 => 30, 4 => 60, 5 => 50]);
        $variance = (float) config('game.bar.price_variance', 0.20);
        $discount = (float) config("game.bar.trader_discount.{$traderRank}", 0.0);

        // At rank 3 Corvan has compound connections — bias towards compounds.
        $compoundsBias = (float) config('game.merchant.commodity.compounds_bias_at_rank3', 0.50);
        if ($traderRank >= 3 && $this->pseudoRand($seed + 10, 0, 99) < (int) ($compoundsBias * 100)) {
            $getResId = 4; // compounds
        } else {
            $getResId = self::TRADEABLE[$this->pseudoRand($seed + 1, 0, count(self::TRADEABLE) - 1)];
        }
        $getAmount = $this->pseudoRand($seed + 2, 1, 5) * 10; // 10–50 units
        $basePrice = $basePrices[$getResId] ?? 40;
        $rawPrice = $basePrice * (1 + ($this->pseudoRand($seed + 3, -10, 10) / 100) * ($variance / 0.2));
        $unitPrice = max(0.01, $rawPrice * (1 - $discount));

        // Losgröße an die Zahlungsfähigkeit binden (höchstens ~35% des
        // Credits-Bestands), sonst kostet ein Angebot ein Vielfaches des
        // Netto-Einkommens und ist faktisch nie annehmbar.
        $affordableCap = max(10, (int) floor($credits * 0.35));
        if ($unitPrice > $affordableCap) {
            return null;
        }
        $getAmount = min($getAmount, max(1, (int) floor($affordableCap / $unitPrice)));
        $finalPrice = (int) max(1, round($unitPrice * $getAmount));

        return [self::RES_CREDITS, $finalPrice, $getResId, $getAmount];
    }

    /** Trader advisor (Konsul) rank for a colony — 0 if none assigned or unavailable. */
    public function traderRank(int $colonyId): int
    {
        return (int) (DB::table('advisors')
            ->where('colony_id', $colonyId)
            ->where('personell_id', self::TRADER_ADVISOR_ID)
            ->whereNull('unavailable_until_tick')
            ->value('rank') ?? 0);
    }

    /** Barter: player gives one resource, gets another (no credits involved). */
    private function buildBarterOffer(int $seed, array $basePrices, float $discount): array
    {
        $shuffled = self::TRADEABLE;
        $giveResId = $shuffled[$this->pseudoRand($seed + 4, 0, count($shuffled) - 1)];
        $getResId = $shuffled[$this->pseudoRand($seed + 5, 0, count($shuffled) - 1)];
        if ($getResId === $giveResId) {
            $getResId = $shuffled[($this->pseudoRand($seed + 5, 0, count($shuffled) - 1) + 1) % count($shuffled)];
        }
        $giveAmount = $this->pseudoRand($seed + 6, 2, 6) * 5; // 10–30 units
        $givePrice = ($basePrices[$giveResId] ?? 40) * $giveAmount;
        $getPrice = ($basePrices[$getResId] ?? 40);
        $getAmount = (int) max(1, round($givePrice * (1 + $discount) / $getPrice));

        return [$giveResId, $giveAmount, $getResId, $getAmount];
    }

    private function pseudoRand(int $seed, int $min, int $max): int
    {
        if ($min >= $max) {
            return $min;
        }
        $hash = abs(($seed * 1664525 + 1013904223) & 0x7FFFFFFF);

        return $min + ($hash % ($max - $min + 1));
    }
}
