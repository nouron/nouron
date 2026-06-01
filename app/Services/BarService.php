<?php

namespace App\Services;

use App\Models\BarOffer;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BarService
{
    private const BAR_BUILDING_ID   = 52;
    private const TRADER_ADVISOR_ID = 92; // personell.id for 'trader'
    private const RES_CREDITS       = 1;
    private const TRADEABLE         = [3, 4, 5]; // regolith, compounds, organics

    public function __construct(
        private readonly ResourcesService $resourcesService,
    ) {}

    public function generateOffersForColony(int $colonyId, int $tick): void
    {
        // Bar must exist and be level ≥ 1
        $barLevel = (int) DB::table('colony_buildings')
            ->where('colony_id', $colonyId)
            ->where('building_id', self::BAR_BUILDING_ID)
            ->value('level') ?? 0;

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
        $traderRank = (int) (DB::table('advisors')
            ->where('colony_id', $colonyId)
            ->where('personell_id', self::TRADER_ADVISOR_ID)
            ->whereNull('unavailable_until_tick')
            ->value('rank') ?? 0);

        [$minGuests, $maxGuests] = config("game.bar.guest_count.{$traderRank}", [0, 1]);
        $guestCount = $this->pseudoRand($colonyId * 997 + $tick * 31, $minGuests, $maxGuests);

        if ($guestCount < 1) {
            return;
        }

        $duration = (int) config('game.bar.offer_duration', 2);
        $expiresTick = $tick + $duration;
        $discount    = (float) config("game.bar.trader_discount.{$traderRank}", 0.0);
        $basePrices  = config('game.bar.base_prices', [3 => 30, 4 => 60, 5 => 50]);
        $variance    = (float) config('game.bar.price_variance', 0.20);

        for ($i = 0; $i < $guestCount; $i++) {
            $seed = $colonyId * 1009 + $tick * 127 + $i * 37;
            [$giveResId, $giveAmount, $getResId, $getAmount] =
                $this->buildOffer($seed, $basePrices, $variance, $discount);

            BarOffer::create([
                'colony_id'       => $colonyId,
                'give_resource_id' => $giveResId,
                'give_amount'      => $giveAmount,
                'get_resource_id'  => $getResId,
                'get_amount'       => $getAmount,
                'expires_tick'     => $expiresTick,
                'is_accepted'      => false,
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

    public function acceptOffer(int $colonyId, int $offerId, int $userId, int $currentTick): array
    {
        $offer = BarOffer::where('id', $offerId)
            ->where('colony_id', $colonyId)
            ->first();

        if (!$offer) {
            return ['ok' => false, 'error' => __('colony.bar_offer_not_found')];
        }
        if ($offer->is_accepted) {
            return ['ok' => false, 'error' => __('colony.bar_offer_already_accepted')];
        }

        if ($offer->expires_tick <= $currentTick) {
            return ['ok' => false, 'error' => __('colony.bar_offer_expired')];
        }

        // Check player can afford the give side
        $giveBalance = $this->getResourceBalance($colonyId, $userId, $offer->give_resource_id);
        if ($giveBalance < $offer->give_amount) {
            return ['ok' => false, 'error' => __('colony.bar_offer_insufficient_resources')];
        }

        // Execute trade atomically — partial transfer must not persist
        DB::transaction(function () use ($offer, $colonyId): void {
            $this->resourcesService->decreaseAmount($colonyId, $offer->give_resource_id, $offer->give_amount);
            $this->resourcesService->increaseAmount($colonyId, $offer->get_resource_id, $offer->get_amount);
            $offer->is_accepted = true;
            $offer->save();
        });

        return ['ok' => true];
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
     * Build a single offer deterministically from seed.
     * Returns [give_resource_id, give_amount, get_resource_id, get_amount].
     * Offer types: resource→credits (buy) or barter (resource↔resource).
     */
    private function buildOffer(int $seed, array $basePrices, float $variance, float $discount): array
    {
        // 60% resource-for-credits, 40% barter
        $type = $this->pseudoRand($seed, 0, 9);

        if ($type < 6) {
            // Player pays Credits, gets a tradeable resource
            $getResId  = self::TRADEABLE[$this->pseudoRand($seed + 1, 0, count(self::TRADEABLE) - 1)];
            $getAmount = $this->pseudoRand($seed + 2, 1, 5) * 10; // 10–50 units
            $basePrice = $basePrices[$getResId] ?? 40;
            $rawPrice  = $basePrice * (1 + ($this->pseudoRand($seed + 3, -10, 10) / 100) * ($variance / 0.2));
            $finalPrice = (int) max(1, round($rawPrice * $getAmount * (1 - $discount)));
            return [self::RES_CREDITS, $finalPrice, $getResId, $getAmount];
        } else {
            // Barter: player gives one resource, gets another
            $shuffled  = self::TRADEABLE;
            $giveResId = $shuffled[$this->pseudoRand($seed + 4, 0, count($shuffled) - 1)];
            $getResId  = $shuffled[$this->pseudoRand($seed + 5, 0, count($shuffled) - 1)];
            if ($getResId === $giveResId) {
                $getResId = $shuffled[($this->pseudoRand($seed + 5, 0, count($shuffled) - 1) + 1) % count($shuffled)];
            }
            $giveAmount = $this->pseudoRand($seed + 6, 2, 6) * 5; // 10–30 units
            $givePrice  = ($basePrices[$giveResId] ?? 40) * $giveAmount;
            $getPrice   = ($basePrices[$getResId] ?? 40);
            $getAmount  = (int) max(1, round($givePrice * (1 + $discount) / $getPrice));
            return [$giveResId, $giveAmount, $getResId, $getAmount];
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
}
