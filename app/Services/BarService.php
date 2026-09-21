<?php

namespace App\Services;

use App\Console\Commands\GameTick;
use App\Models\BarConcern;
use App\Models\BarEncounter;
use App\Models\BarInformationEncounter;
use App\Models\BarOffer;
use App\Services\Techtree\ResearchService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BarService
{
    private const BAR_BUILDING_ID = 52;

    private const TRADER_ADVISOR_ID = 92; // personell.id for 'trader'

    private const RES_CREDITS = 1;

    private const RES_REGOLITH = 3;

    private const RES_COMPOUNDS = 4;

    private const TRADEABLE = [3, 4, 5]; // regolith, compounds, organics

    /** config/characters.php keys eligible for a Charakter-Anliegen (A41). */
    private const CONCERN_CHARACTERS = [
        'smuggler', 'information_broker', 'mechanic', 'doctor',
        'prospector', 'mercenary', 'founder', 'preacher', 'stranger',
    ];

    public function __construct(
        private readonly ResourcesService $resourcesService,
        private readonly AdvisorService $advisorService,
        private readonly ResearchService $researchService,
        private readonly TrustService $trustService,
        private readonly HangarService $hangarService,
        private readonly CharacterCodexService $characterCodexService,
        private readonly TradeAdvantageService $tradeAdvantageService,
        private readonly ProjectBonusService $projectBonusService,
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
        $maxConcurrent = ($levelMaxConcurrent[$barLevel] ?? 2) + $this->tradeConcurrentSlotBonus($colonyId);
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
        $basePrices = config('game.bar.base_prices', [3 => 30, 4 => 60, 5 => 50]);

        for ($i = 0; $i < $guestCount; $i++) {
            $seed = $colonyId * 1009 + $tick * 127 + $i * 37;
            // Base terms only — the Handelsvorteil (Konsul, Handelsposten, trade
            // knowledge) is applied at display/accept time by TradeAdvantageService.
            [$giveResId, $giveAmount, $getResId, $getAmount] =
                $this->buildBarterOffer($seed, $basePrices);

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

    /**
     * Trade knowledge bonus on concurrent Cantina offer slots (GDD §13.5 Pfad-C,
     * docs/superpowers/specs/2026-08-15-knowledge-effects-and-encounters-design.md §4)
     * — separate from the build-AP discount (ProjectBonusService), since this effect
     * concerns actions (Cantina offers), not projects.
     */
    private function tradeConcurrentSlotBonus(int $colonyId): int
    {
        $tradeId = (int) config('knowledge.trade.id', 95);
        $tradeLevel = (int) DB::table('colony_researches')
            ->where('colony_id', $colonyId)
            ->where('research_id', $tradeId)
            ->value('level');

        return GameTick::cumulativeCurveYield(
            config('knowledge.trade.bar_offer_boost_per_lv', []),
            $tradeLevel
        );
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

    /**
     * @param  string|null  $characterSlug  The config/characters.php slug of
     *                                      the figure whose personalized
     *                                      flavor line was shown for this
     *                                      guest offer (bar_trade_flavor_*,
     *                                      A36) — a UI-only assignment
     *                                      (resources/views/colony/bar.blade.php),
     *                                      not persisted on bar_offers, so
     *                                      the caller passes it through
     *                                      explicitly. Only credited toward
     *                                      Charakter-Kodex (A42) progress
     *                                      when the slug's game_role is
     *                                      'bar_trade' — every other role
     *                                      has its own dedicated trigger.
     */
    public function acceptOffer(int $colonyId, int $offerId, int $userId, int $currentTick, ?string $characterSlug = null): array
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

        // Handelsvorteil (GDD §12, A13): the executed terms come from the very
        // same computation the Cantina page displays (effectiveTerms()). Computed
        // BEFORE the affordability check so it runs against the amount actually due.
        $terms = $this->effectiveTerms($offer, $colonyId);
        $giveAmount = $terms['give_amount'];
        $getAmount = $terms['get_amount'];

        // Check player can afford the give side
        $giveBalance = $this->getResourceBalance($colonyId, $userId, $offer->give_resource_id);
        if ($giveBalance < $giveAmount) {
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
                if (($giveBalance - $giveAmount) < $reserve) {
                    return ['ok' => false, 'error' => __('colony.bar_offer_reserve_floor')];
                }
            }
        }

        // Execute trade atomically — partial transfer must not persist
        DB::transaction(function () use ($offer, $colonyId, $apCost, $giveAmount, $getAmount): void {
            $this->resourcesService->decreaseAmount($colonyId, $offer->give_resource_id, $giveAmount);
            $this->resourcesService->increaseAmount($colonyId, $offer->get_resource_id, $getAmount);
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
            'give_amount' => $giveAmount,
            'get_resource_id' => $offer->get_resource_id,
            'get_amount' => $getAmount,
        ]);

        // Charakter-Kodex (A42) — bar_trade figures only (their dedicated
        // trigger: a completed trade shown with their personalized flavor
        // line). Every other game_role has its own trigger elsewhere.
        if ($characterSlug !== null && (config("characters.{$characterSlug}.game_role") === 'bar_trade')) {
            $this->characterCodexService->recordProgress($userId, $characterSlug);
        }

        return [
            'ok' => true,
            'give_resource_id' => $offer->give_resource_id,
            'give_amount' => $giveAmount,
            'get_resource_id' => $offer->get_resource_id,
            'get_amount' => $getAmount,
        ];
    }

    /**
     * Cantina-Verhandlung (Risiko-Handel, GDD §12 Kanal 1) — alternative resolution
     * path for a bar offer. Requires an assigned, available Konsul (trader advisor).
     * Costs ap_cost_negotiate (equal to Annehmen) on success AND failure; two-step outcome:
     *   - Success: the offer is only flagged is_negotiated — NOTHING is written into its
     *     amounts. effectiveTerms() adds the negotiation bonus on top of the Handelsvorteil
     *     (additive, Get side); the trade does NOT execute yet, the player still confirms
     *     with acceptOffer() (which waives its AP cost for a negotiated offer).
     *   - Failure: the offer is lost entirely (deleted) — no trade, the Give side stays
     *     with the player, no fallback to accept. The result carries the facts for the UI.
     * The success chance comes from negotiateChance() — the same number the dialog shows.
     *
     * Result on success: ok, success, give_/get_resource_id + give_/get_amount (= what
     * acceptOffer() will execute) and `terms` (the full effectiveTerms() array; P2b builds
     * the offer dialog on it). On failure: ok, success=false, give_resource_id + give_amount
     * (stays with the player) and ap_spent.
     *
     * @return array<string, mixed>
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
        if ($this->isFixedPriceOffer($offer)) {
            return ['ok' => false, 'error' => __('colony.bar_offer_not_negotiable')];
        }

        if ($this->traderRank($colonyId) < 1) {
            return ['ok' => false, 'error' => __('colony.bar_offer_no_consul')];
        }

        $apCost = (int) config('game.bar.ap_cost_negotiate', 2);
        if ($apCost > 0 && ! config('game.bypass.ap_checks')) {
            $availableAp = $this->advisorService->getAvailableActionPoints($colonyId);
            if ($availableAp < $apCost) {
                return ['ok' => false, 'error' => __('colony.bar_offer_insufficient_ap')];
            }
        }

        // The Give side never changes (neither by Handelsvorteil nor by negotiation).
        $giveBalance = $this->getResourceBalance($colonyId, $userId, $offer->give_resource_id);
        if ($giveBalance < $offer->give_amount) {
            return ['ok' => false, 'error' => __('colony.bar_offer_insufficient_resources')];
        }

        $chancePercent = $this->negotiateChance($colonyId)['total_percent'];
        $roll = $this->pseudoRand($offer->id * 7919 + $currentTick * 131, 0, 99);
        $success = $roll < $chancePercent;

        return DB::transaction(function () use ($offer, $colonyId, $apCost, $success, $chancePercent): array {
            if ($apCost > 0) {
                $this->advisorService->lockActionPoints($colonyId, $apCost, self::TRADER_ADVISOR_ID);
            }

            if (! $success) {
                $offer->delete();

                Log::info('bar_trade_negotiate_failed', [
                    'colony_id' => $colonyId,
                    'offer_id' => $offer->id,
                    'chance_percent' => $chancePercent,
                ]);

                return [
                    'ok' => true,
                    'success' => false,
                    'give_resource_id' => (int) $offer->give_resource_id,
                    'give_amount' => (int) $offer->give_amount,
                    'ap_spent' => $apCost,
                ];
            }

            $offer->is_negotiated = true;
            $offer->save();

            $terms = $this->effectiveTerms($offer, $colonyId);

            Log::info('bar_trade_negotiate_success', [
                'colony_id' => $colonyId,
                'offer_id' => $offer->id,
                'chance_percent' => $chancePercent,
                'give_resource_id' => $terms['give_resource_id'],
                'give_amount' => $terms['give_amount'],
                'get_resource_id' => $terms['get_resource_id'],
                'get_amount' => $terms['get_amount'],
            ]);

            return [
                'ok' => true,
                'success' => true,
                'give_resource_id' => $terms['give_resource_id'],
                'give_amount' => $terms['give_amount'],
                'get_resource_id' => $terms['get_resource_id'],
                'get_amount' => $terms['get_amount'],
                'terms' => $terms,
            ];
        });
    }

    /**
     * Success chance of a Cantina-Verhandlung for a colony — THE single source for the
     * roll in negotiateOffer() and for every display (offer dialog, P2b), so the shown
     * chance is the rolled chance (GDD §12, A13).
     *
     * base            = game.bar.negotiate_success_chance[Konsul rank] (0 without an available Konsul)
     * knowledge_bonus = knowledge.trade.negotiate_chance_bonus_per_lv, cumulative (only with a Konsul)
     * total           = base + knowledge_bonus, silently capped at game.bar.trade_terms.negotiate_chance_max
     * Percent fields are whole percentage points (the roll compares 0..99 against total_percent);
     * `capped` tells the caller that total is below base + knowledge_bonus.
     *
     * @return array{
     *     rank: int, base: float, base_percent: int, knowledge_bonus: float, knowledge_bonus_percent: int,
     *     total: float, total_percent: int, capped: bool
     * }
     */
    public function negotiateChance(int $colonyId): array
    {
        $rank = $this->traderRank($colonyId);

        $basePercent = (int) round(((float) config("game.bar.negotiate_success_chance.{$rank}", 0.0)) * 100);
        $bonusPercent = $rank > 0 ? $this->projectBonusService->tradeNegotiateChanceBonusPercent($colonyId) : 0;
        $maxPercent = (int) round(((float) config('game.bar.trade_terms.negotiate_chance_max', 1.0)) * 100);

        $uncapped = $basePercent + $bonusPercent;
        $totalPercent = min($uncapped, $maxPercent);

        return [
            'rank' => $rank,
            'base' => $basePercent / 100,
            'base_percent' => $basePercent,
            'knowledge_bonus' => $bonusPercent / 100,
            'knowledge_bonus_percent' => $bonusPercent,
            'total' => $totalPercent / 100,
            'total_percent' => $totalPercent,
            'capped' => $uncapped > $maxPercent,
        ];
    }

    /**
     * The negotiation bonus a successful Verhandeln adds to the Get side (additive to the
     * Handelsvorteil). Constant per rank in config; an already negotiated offer keeps at
     * least the rank-1 value even if the Konsul has since left on a mission — the success
     * was earned and must not silently void.
     */
    public function negotiationBonus(int $colonyId): float
    {
        $rank = max(1, $this->traderRank($colonyId));

        return (float) config("game.bar.negotiate_bonus.{$rank}", 0.0);
    }

    /**
     * Corvan's Organika sell lots (visit_id set, Credits on the Get side) are
     * fixed-price: no Handelsvorteil, no negotiation (GDD §12, A13 — otherwise
     * passive infrastructure would turn into a reliable Credits income and a
     * buy-and-resell loop).
     */
    public function isFixedPriceOffer(object $offer): bool
    {
        return (bool) config('game.bar.trade_terms.fixed_price_offers', true)
            && $offer->visit_id !== null
            && (int) $offer->get_resource_id === self::RES_CREDITS;
    }

    /**
     * The terms a bar offer actually executes at — THE single source for both
     * acceptOffer() (execution) and BarController (display), so the shown
     * amounts can never differ from the booked ones.
     *
     * The stored offer holds base terms only. The Get side is
     * base x (1 + Handelsvorteil + negotiation bonus) — one additive sum, "+X %" means
     * "X % more goods" (Konsul + Handelsposten + trade knowledge in the advantage, the
     * bonus only once the offer is flagged is_negotiated). The Give side (the price)
     * never changes. Fixed-price lots get neither advantage nor bonus.
     *
     * P2b builds the offer dialog on this array: `advantage.sources` for the Handelsvorteil
     * lines, `negotiation_bonus` (applied) / `negotiation_bonus_offered` (what success would
     * add) for the negotiation line, `get_amount_if_negotiated` for the "amount on success" figure (null when nothing to negotiate: already negotiated
     * or fixed price).
     *
     * @return array{
     *     give_resource_id: int, give_amount: int, get_resource_id: int, get_amount: int,
     *     base_give_amount: int, base_get_amount: int, fixed_price: bool, negotiated: bool,
     *     negotiation_bonus: float, negotiation_bonus_offered: float, get_amount_if_negotiated: int|null, advantage: array
     * }
     */
    public function effectiveTerms(object $offer, int $colonyId): array
    {
        $fixedPrice = $this->isFixedPriceOffer($offer);
        $negotiated = ! $fixedPrice && (bool) ($offer->is_negotiated ?? false);

        $advantage = $this->tradeAdvantageService->forChannel(
            $colonyId,
            TradeAdvantageService::CHANNEL_BAR,
            $fixedPrice
                ? [TradeAdvantageService::SOURCE_CONSUL, TradeAdvantageService::SOURCE_TRADING_POST, TradeAdvantageService::SOURCE_TRADE_KNOWLEDGE]
                : [],
        );

        $baseGive = (int) $offer->give_amount;
        $baseGet = (int) $offer->get_amount;
        $bonus = $fixedPrice ? 0.0 : $this->negotiationBonus($colonyId);
        $applied = $negotiated ? $bonus : 0.0;

        return [
            'give_resource_id' => (int) $offer->give_resource_id,
            'give_amount' => $baseGive,
            'get_resource_id' => (int) $offer->get_resource_id,
            'get_amount' => $this->tradeAdvantageService->applyToAmount($baseGet, $advantage, $applied),
            'base_give_amount' => $baseGive,
            'base_get_amount' => $baseGet,
            'fixed_price' => $fixedPrice,
            'negotiated' => $negotiated,
            'negotiation_bonus' => $applied,
            'negotiation_bonus_offered' => $bonus,
            'get_amount_if_negotiated' => ($fixedPrice || $negotiated)
                ? null
                : $this->tradeAdvantageService->applyToAmount($baseGet, $advantage, $bonus),
            'advantage' => $advantage,
        ];
    }

    /**
     * Cantina-Begegnungspool (GDD §12 Kanal 1, A35) — a single shared event slot,
     * one roll picks at most one of the three Credits-outcomes. Deliberately NOT
     * three independent spawn checks (spam/stacking risk with Corvan + guest
     * rotation), and deliberately scaled by BAR LEVEL, never Konsul rank — that
     * coupling is exactly what got the struck Konsul-Handelsvertrag removed.
     */
    /**
     * @return bool Whether a new encounter was actually created this tick — the
     *              Charakter-Anliegen slot (generateConcernForColony(), A41) is
     *              only rolled when this returns false, enforcing "at most one
     *              Cantina special event per tick" across the two pools.
     */
    public function generateEncounterForColony(int $colonyId, int $tick): bool
    {
        $barLevel = (int) DB::table('colony_buildings')
            ->where('colony_id', $colonyId)
            ->where('building_id', self::BAR_BUILDING_ID)
            ->value('level');

        if ($barLevel < 1) {
            return false;
        }

        // Expire old, unaccepted encounters.
        DB::table('bar_encounters')
            ->where('colony_id', $colonyId)
            ->where('expires_tick', '<=', $tick)
            ->where('is_accepted', false)
            ->delete();

        // Only one open slot at a time — an existing pending (or running
        // contract) encounter blocks a new roll.
        $hasOpenEncounter = DB::table('bar_encounters')
            ->where('colony_id', $colonyId)
            ->where(function ($q) use ($tick) {
                $q->where(function ($q2) use ($tick) {
                    $q2->where('is_accepted', false)->where('expires_tick', '>', $tick);
                })->orWhere(function ($q2) {
                    $q2->where('is_accepted', true)->where('resolved', false);
                });
            })
            ->exists();

        if ($hasOpenEncounter) {
            return false;
        }

        $chance = (float) config("game.bar.encounter.spawn_chance_per_level.{$barLevel}", 0.0);
        $roll = $this->pseudoRand($colonyId * 613 + $tick * 47, 0, 999);
        if ($roll >= (int) round($chance * 1000)) {
            return false;
        }

        $types = ['wager', 'auction', 'contract'];
        $type = $types[$this->pseudoRand($colonyId * 883 + $tick * 71, 0, count($types) - 1)];
        $duration = (int) config('game.bar.encounter.offer_duration', 2);
        $expiresTick = $tick + $duration;

        $attributes = match ($type) {
            'wager' => [
                'give_resource_id' => (int) config('game.bar.encounter.wager.stake_resource_id', 3),
                'give_amount' => (int) config("game.bar.encounter.wager.stake_amount_per_level.{$barLevel}", 0),
                'win_chance' => (float) config('game.bar.encounter.wager.win_chance', 0.0),
                'credits_amount' => (int) config("game.bar.encounter.wager.payout_credits_per_level.{$barLevel}", 0),
                'duration_ticks' => null,
            ],
            'auction' => [
                'give_resource_id' => (int) config('game.bar.encounter.auction.give_resource_id', 5),
                'give_amount' => (int) config("game.bar.encounter.auction.give_amount_per_level.{$barLevel}", 0),
                'win_chance' => null,
                'credits_amount' => (int) config("game.bar.encounter.auction.payout_credits_per_level.{$barLevel}", 0),
                'duration_ticks' => null,
            ],
            default => [
                'give_resource_id' => null,
                'give_amount' => null,
                'win_chance' => null,
                'credits_amount' => (int) config("game.bar.encounter.contract.credits_per_tick_per_level.{$barLevel}", 0),
                'duration_ticks' => (int) config('game.bar.encounter.contract.duration_ticks', 1),
            ],
        };

        BarEncounter::create(array_merge([
            'colony_id' => $colonyId,
            'type' => $type,
            'expires_tick' => $expiresTick,
            'is_accepted' => false,
            'resolved' => false,
        ], $attributes));

        return true;
    }

    public function getActiveEncounter(int $colonyId, int $tick): ?BarEncounter
    {
        return BarEncounter::where('colony_id', $colonyId)
            ->where(function ($q) use ($tick) {
                $q->where(function ($q2) use ($tick) {
                    $q2->where('is_accepted', false)->where('expires_tick', '>', $tick);
                })->orWhere(function ($q2) {
                    $q2->where('is_accepted', true)->where('resolved', false);
                });
            })
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Accept a Cantina-Begegnungspool encounter. Wager and auction resolve
     * immediately (chance-based / guaranteed); a contract only starts running —
     * its Credits/tick payouts are processed by GameTick until ends_tick.
     */
    public function acceptEncounter(int $colonyId, int $encounterId, int $userId, int $currentTick): array
    {
        $encounter = BarEncounter::where('id', $encounterId)
            ->where('colony_id', $colonyId)
            ->first();

        if (! $encounter) {
            return ['ok' => false, 'error' => __('colony.bar_encounter_not_found')];
        }
        if ($encounter->is_accepted) {
            return ['ok' => false, 'error' => __('colony.bar_encounter_already_accepted')];
        }
        if ($encounter->expires_tick <= $currentTick) {
            return ['ok' => false, 'error' => __('colony.bar_encounter_expired')];
        }

        $apCost = (int) config('game.bar.encounter.ap_cost_accept', 0);
        if ($apCost > 0 && ! config('game.bypass.ap_checks')) {
            $availableAp = $this->advisorService->getAvailableActionPoints($colonyId);
            if ($availableAp < $apCost) {
                return ['ok' => false, 'error' => __('colony.bar_encounter_insufficient_ap')];
            }
        }

        if ($encounter->give_resource_id !== null) {
            $balance = $this->getResourceBalance($colonyId, $userId, $encounter->give_resource_id);
            if ($balance < $encounter->give_amount) {
                return ['ok' => false, 'error' => __('colony.bar_encounter_insufficient_resources')];
            }
        }

        return DB::transaction(function () use ($encounter, $colonyId, $userId, $apCost, $currentTick): array {
            if ($apCost > 0) {
                $this->advisorService->lockActionPoints($colonyId, $apCost, self::TRADER_ADVISOR_ID);
            }

            if ($encounter->give_resource_id !== null) {
                $this->resourcesService->decreaseAmount($colonyId, $encounter->give_resource_id, $encounter->give_amount);
            }

            return match ($encounter->type) {
                'wager' => $this->resolveWager($encounter, $colonyId, $userId, $currentTick),
                'auction' => $this->resolveAuction($encounter, $colonyId, $userId),
                default => $this->startContract($encounter, $currentTick),
            };
        });
    }

    /**
     * @param  int  $userId  Only used to record Charakter-Kodex progress (A42)
     *                       for Zara (gambler) — her `dedicated` game_role.
     */
    private function resolveWager(BarEncounter $encounter, int $colonyId, int $userId, int $currentTick): array
    {
        $roll = $this->pseudoRand($encounter->id * 7351 + $currentTick * 211, 0, 999);
        $won = $roll < (int) round($encounter->win_chance * 1000);

        if ($won) {
            $this->resourcesService->increaseAmount($colonyId, self::RES_CREDITS, $encounter->credits_amount);
        }

        $encounter->is_accepted = true;
        $encounter->resolved = true;
        $encounter->won = $won;
        $encounter->save();

        Log::info('bar_encounter_wager', [
            'colony_id' => $colonyId,
            'encounter_id' => $encounter->id,
            'won' => $won,
        ]);

        // Charakter-Kodex (A42) — Zara's (gambler) dedicated mechanic.
        $this->characterCodexService->recordProgress($userId, 'gambler');

        return [
            'ok' => true,
            'type' => 'wager',
            'won' => $won,
            'credits_amount' => $won ? $encounter->credits_amount : 0,
            'give_resource_id' => $encounter->give_resource_id,
        ];
    }

    /**
     * @param  int  $userId  Only used to record Charakter-Kodex progress (A42)
     *                       for Voss (scrap_dealer) — his `dedicated` game_role.
     */
    private function resolveAuction(BarEncounter $encounter, int $colonyId, int $userId): array
    {
        $this->resourcesService->increaseAmount($colonyId, self::RES_CREDITS, $encounter->credits_amount);

        $encounter->is_accepted = true;
        $encounter->resolved = true;
        $encounter->save();

        Log::info('bar_encounter_auction', [
            'colony_id' => $colonyId,
            'encounter_id' => $encounter->id,
            'credits_amount' => $encounter->credits_amount,
        ]);

        // Charakter-Kodex (A42) — Voss's (scrap_dealer) dedicated mechanic.
        $this->characterCodexService->recordProgress($userId, 'scrap_dealer');

        return [
            'ok' => true,
            'type' => 'auction',
            'credits_amount' => $encounter->credits_amount,
            'give_resource_id' => $encounter->give_resource_id,
        ];
    }

    private function startContract(BarEncounter $encounter, int $currentTick): array
    {
        $encounter->is_accepted = true;
        $encounter->ends_tick = $currentTick + $encounter->duration_ticks;
        $encounter->save();

        return [
            'ok' => true,
            'type' => 'contract',
            'ends_tick' => $encounter->ends_tick,
        ];
    }

    /**
     * Charakter-Anliegen (A41, GDD §12 Kanal 1) — a second, independent
     * Cantina special-event slot. Only rolled when the shared encounter slot
     * (generateEncounterForColony()) did NOT fire this tick — at most one
     * Cantina special event per tick, total, across both pools.
     */
    public function generateConcernForColony(int $colonyId, int $tick, bool $encounterFiredThisTick): void
    {
        if ($encounterFiredThisTick) {
            return;
        }

        $barLevel = (int) DB::table('colony_buildings')
            ->where('colony_id', $colonyId)
            ->where('building_id', self::BAR_BUILDING_ID)
            ->value('level');

        if ($barLevel < 1) {
            return;
        }

        // Expire old, unresolved concerns.
        DB::table('bar_concerns')
            ->where('colony_id', $colonyId)
            ->where('expires_tick', '<=', $tick)
            ->where('is_resolved', false)
            ->delete();

        // Only one open slot at a time.
        $hasOpenConcern = DB::table('bar_concerns')
            ->where('colony_id', $colonyId)
            ->where('is_resolved', false)
            ->where('expires_tick', '>', $tick)
            ->exists();

        if ($hasOpenConcern) {
            return;
        }

        $chance = (float) config("game.bar.concern.spawn_chance_per_level.{$barLevel}", 0.0);
        $roll = $this->pseudoRand($colonyId * 4021 + $tick * 59, 0, 999);
        if ($roll >= (int) round($chance * 1000)) {
            return;
        }

        $cooldownSols = (int) config('game.bar.concern.character_cooldown_sols', 0);
        $weights = config('game.bar.concern.character_weights', []);

        $eligible = [];
        foreach (self::CONCERN_CHARACTERS as $slug) {
            $weight = (int) ($weights[$slug] ?? 0);
            if ($weight <= 0) {
                continue;
            }

            $lastCreatedTick = DB::table('bar_concerns')
                ->where('colony_id', $colonyId)
                ->where('character_slug', $slug)
                ->max('created_tick');

            if ($lastCreatedTick !== null && $tick - (int) $lastCreatedTick < $cooldownSols) {
                continue;
            }

            for ($i = 0; $i < $weight; $i++) {
                $eligible[] = $slug;
            }
        }

        if (empty($eligible)) {
            return;
        }

        $slug = $eligible[$this->pseudoRand($colonyId * 4523 + $tick * 83, 0, count($eligible) - 1)];
        $duration = (int) config('game.bar.concern.offer_duration', 2);

        BarConcern::create([
            'colony_id' => $colonyId,
            'character_slug' => $slug,
            'created_tick' => $tick,
            'expires_tick' => $tick + $duration,
            'is_resolved' => false,
        ]);
    }

    public function getActiveConcern(int $colonyId, int $tick): ?BarConcern
    {
        return BarConcern::where('colony_id', $colonyId)
            ->where('is_resolved', false)
            ->where('expires_tick', '>', $tick)
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Resolves a Charakter-Anliegen (A41). AP cost comes out of the shared
     * colony pool like any other Cantina action — Sarka's ap_bonus reward
     * itself is the one exception, injected via ResearchService::investBonus()
     * (earmarked, same mechanism as Tomas' bartender bonus, A40).
     *
     * @param  int  $knowledgeId  Required only for the mechanic (Sarka) concern
     *                            — the knowledge the player chooses to invest
     *                            Sarka's bonus AP into.
     */
    public function resolveConcern(int $colonyId, int $concernId, int $userId, int $currentTick, ?int $knowledgeId = null): array
    {
        $concern = BarConcern::where('id', $concernId)
            ->where('colony_id', $colonyId)
            ->first();

        if (! $concern) {
            return ['ok' => false, 'error' => __('colony.bar_concern_not_found')];
        }
        if ($concern->is_resolved) {
            return ['ok' => false, 'error' => __('colony.bar_concern_already_resolved')];
        }
        if ($concern->expires_tick <= $currentTick) {
            return ['ok' => false, 'error' => __('colony.bar_concern_expired')];
        }

        $slug = $concern->character_slug;
        $apCost = (int) config("game.bar.concern.ap_cost.{$slug}", 0);

        if ($apCost > 0 && ! config('game.bypass.ap_checks')) {
            $availableAp = $this->advisorService->getAvailableActionPoints($colonyId);
            if ($availableAp < $apCost) {
                return ['ok' => false, 'error' => __('colony.bar_concern_insufficient_ap')];
            }
        }

        // Stranger stakes Werkstoffe upfront — rolled once here (not re-rolled
        // in resolveConcernOutcome()) so the balance check and the actual
        // deduction always agree on the same amount.
        $strangerStake = null;
        if ($slug === 'stranger') {
            $cfg = config('game.bar.concern.stranger', []);
            $strangerStake = $this->pseudoRand($concern->id * 11 + $currentTick * 13, (int) ($cfg['stake_min'] ?? 0), (int) ($cfg['stake_max'] ?? 0));
            $balance = $this->getResourceBalance($colonyId, $userId, self::RES_COMPOUNDS);
            if ($balance < $strangerStake) {
                return ['ok' => false, 'error' => __('colony.bar_concern_insufficient_resources')];
            }
        }

        $successChance = (float) config("game.bar.concern.success_chance.{$slug}", 0.0);
        $roll = $this->pseudoRand($concern->id * 6113 + $currentTick * 191, 0, 999);
        $success = $roll < (int) round($successChance * 1000);

        return DB::transaction(function () use ($concern, $colonyId, $userId, $apCost, $slug, $success, $currentTick, $knowledgeId, $strangerStake): array {
            if ($apCost > 0) {
                $this->advisorService->lockActionPoints($colonyId, $apCost);
            }

            $outcome = $this->resolveConcernOutcome($colonyId, $slug, $success, $concern->id, $currentTick, $knowledgeId, $strangerStake);

            $concern->is_resolved = true;
            $concern->success = $success;
            $concern->outcome = $outcome;
            $concern->save();

            // Charakter-Kodex (A42) — ONLY the 3 story_hook figures among the
            // 9 concern characters (founder/Aldra, preacher/Sorel, stranger).
            // The other 6 (bar_trade) are credited via acceptOffer()'s
            // personalized-flavor guest trade instead, not via their concern.
            if (in_array($slug, ['founder', 'preacher', 'stranger'], true)) {
                $this->characterCodexService->recordProgress($userId, $slug);
            }

            Log::info('bar_concern_resolved', [
                'colony_id' => $colonyId,
                'concern_id' => $concern->id,
                'character_slug' => $slug,
                'success' => $success,
                'outcome' => $outcome,
            ]);

            return array_merge(['ok' => true, 'character_slug' => $slug, 'success' => $success], $outcome);
        });
    }

    /**
     * Character-specific reward/penalty resolution. Runs inside the caller's
     * DB transaction. Returns an array of outcome details persisted on
     * bar_concerns.outcome (for logging/inspection) and merged into the
     * resolveConcern() response.
     */
    private function resolveConcernOutcome(int $colonyId, string $slug, bool $success, int $seed, int $currentTick, ?int $knowledgeId, ?int $strangerStake): array
    {
        $cfg = config("game.bar.concern.{$slug}", []);

        return match ($slug) {
            'smuggler' => $this->resolveSmugglerConcern($colonyId, $cfg),
            'information_broker' => $this->resolveVoucherConcern($colonyId, $currentTick, 'information_broker', (int) $cfg['discount_pct'], null),
            'mechanic' => $this->resolveMechanicConcern($colonyId, $cfg, $knowledgeId),
            'doctor' => $this->resolveDoctorConcern($colonyId, $cfg),
            'prospector' => $this->resolveProspectorConcern($colonyId, $success, $cfg, $seed, $currentTick),
            'mercenary' => $this->resolveMercenaryConcern($colonyId, $cfg, $seed, $currentTick),
            'founder' => $this->resolveFounderConcern($colonyId, $success, $cfg, $currentTick),
            'preacher' => $this->resolvePreacherConcern($colonyId, $success, $currentTick),
            'stranger' => $this->resolveStrangerConcern($colonyId, $success, $cfg, $seed, $currentTick, (int) $strangerStake),
            default => [],
        };
    }

    private function resolveSmugglerConcern(int $colonyId, array $cfg): array
    {
        $shipId = (int) ($cfg['ship_id'] ?? 85);
        $this->hangarService->grantFreeShip($colonyId, $shipId);

        return ['reward' => 'ship', 'ship_id' => $shipId];
    }

    private function resolveVoucherConcern(int $colonyId, int $currentTick, string $source, int $discountPct, ?int $expiresAfterSols): array
    {
        DB::table('colony_building_discount_vouchers')->insert([
            'colony_id' => $colonyId,
            'discount_pct' => $discountPct,
            'source' => $source,
            'granted_tick' => $currentTick,
            'expires_tick' => $expiresAfterSols !== null ? $currentTick + $expiresAfterSols : null,
            'consumed_tick' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return ['reward' => 'building_discount_voucher', 'discount_pct' => $discountPct];
    }

    private function resolveMechanicConcern(int $colonyId, array $cfg, ?int $knowledgeId): array
    {
        $apBonus = (int) ($cfg['ap_bonus'] ?? 0);

        if ($knowledgeId === null) {
            return ['reward' => 'ap_bonus', 'ap_bonus' => 0, 'error' => 'knowledge_id_required'];
        }

        // Earmarked, same mechanism as Tomas' bartender bonus (A40) — bypasses
        // the shared pool's availability gate for the bonus itself.
        $this->researchService->investBonus($colonyId, $knowledgeId, $apBonus);

        return ['reward' => 'ap_bonus', 'ap_bonus' => $apBonus, 'knowledge_id' => $knowledgeId];
    }

    private function resolveDoctorConcern(int $colonyId, array $cfg): array
    {
        $barLevel = max(1, (int) DB::table('colony_buildings')
            ->where('colony_id', $colonyId)
            ->where('building_id', self::BAR_BUILDING_ID)
            ->value('level'));

        $amount = (int) (($cfg['compounds_amount_per_level'] ?? [])[$barLevel] ?? 0);
        $this->resourcesService->increaseAmount($colonyId, self::RES_COMPOUNDS, $amount);

        return ['reward' => 'compounds', 'amount' => $amount];
    }

    private function resolveProspectorConcern(int $colonyId, bool $success, array $cfg, int $seed, int $currentTick): array
    {
        if (! $success) {
            return ['reward' => 'regolith', 'amount' => 0];
        }

        $amount = $this->pseudoRand($seed * 2 + $currentTick * 3, (int) ($cfg['regolith_min'] ?? 0), (int) ($cfg['regolith_max'] ?? 0));
        $this->resourcesService->increaseAmount($colonyId, self::RES_REGOLITH, $amount);

        return ['reward' => 'regolith', 'amount' => $amount];
    }

    private function resolveMercenaryConcern(int $colonyId, array $cfg, int $seed, int $currentTick): array
    {
        $amount = $this->pseudoRand($seed * 5 + $currentTick * 7, (int) ($cfg['credits_min'] ?? 0), (int) ($cfg['credits_max'] ?? 0));
        $this->resourcesService->increaseAmount($colonyId, self::RES_CREDITS, $amount);

        return ['reward' => 'credits', 'amount' => $amount];
    }

    private function resolveFounderConcern(int $colonyId, bool $success, array $cfg, int $currentTick): array
    {
        if (! $success) {
            return ['reward' => 'building_discount_voucher', 'discount_pct' => 0];
        }

        return $this->resolveVoucherConcern(
            $colonyId,
            $currentTick,
            'founder',
            (int) $cfg['discount_pct'],
            isset($cfg['voucher_expires_sols']) ? (int) $cfg['voucher_expires_sols'] : null
        );
    }

    private function resolvePreacherConcern(int $colonyId, bool $success, int $currentTick): array
    {
        $this->trustService->fireEvent(
            $colonyId,
            $success ? 'story_concern_resolved' : 'story_concern_failed',
            $currentTick
        );

        return ['reward' => 'trust', 'success' => $success];
    }

    private function resolveStrangerConcern(int $colonyId, bool $success, array $cfg, int $seed, int $currentTick, int $stake): array
    {
        $this->resourcesService->decreaseAmount($colonyId, self::RES_COMPOUNDS, $stake);

        if (! $success) {
            return ['reward' => 'credits', 'amount' => 0, 'stake' => $stake];
        }

        $payout = $this->pseudoRand($seed * 17 + $currentTick * 19, (int) ($cfg['payout_min'] ?? 0), (int) ($cfg['payout_max'] ?? 0));
        $this->resourcesService->increaseAmount($colonyId, self::RES_CREDITS, $payout);

        return ['reward' => 'credits', 'amount' => $payout, 'stake' => $stake];
    }

    /**
     * story_hook Cantina-Begegnung (A36) — pure flavor, no resource/Credits
     * effect (Leitplanke §12: not every figure gets an economic tie-in).
     * Deterministic per colony+tick (no DB state): same roll all Sol.
     */
    public function pickStoryEncounter(int $colonyId, int $tick): ?string
    {
        $chance = (float) config('game.bar.story_encounter.chance', 0.0);
        $slugs = config('game.bar.story_encounter.slugs', []);
        if (empty($slugs)) {
            return null;
        }

        $roll = $this->pseudoRand($colonyId * 5099 + $tick * 233, 0, 999);
        if ($roll >= (int) round($chance * 1000)) {
            return null;
        }

        return $slugs[$this->pseudoRand($colonyId * 3169 + $tick * 149, 0, count($slugs) - 1)];
    }

    /**
     * Deva & Lenn Vier-Ausgänge-Pool (GDD §12 "Deva & Lenn — taktische
     * Information", A42) — a third, independent Cantina special-event
     * channel. Deliberately NOT gated by the "at most one Cantina special
     * event per tick" rule shared by generateEncounterForColony() (A35) /
     * generateConcernForColony() (A41) — this pool rolls on its own, every
     * tick, in parallel, with its own flat (not bar-level-scaled) chance.
     */
    public function generateInformationEncounterForColony(int $colonyId, int $tick): void
    {
        $barLevel = (int) DB::table('colony_buildings')
            ->where('colony_id', $colonyId)
            ->where('building_id', self::BAR_BUILDING_ID)
            ->value('level');

        if ($barLevel < 1) {
            return;
        }

        DB::table('bar_information_encounters')
            ->where('colony_id', $colonyId)
            ->where('expires_tick', '<=', $tick)
            ->where('is_resolved', false)
            ->delete();

        $hasOpenEncounter = DB::table('bar_information_encounters')
            ->where('colony_id', $colonyId)
            ->where('is_resolved', false)
            ->where('expires_tick', '>', $tick)
            ->exists();

        if ($hasOpenEncounter) {
            return;
        }

        $chance = (float) config('game.bar.information_pool.spawn_chance_per_tick', 0.0);
        $roll = $this->pseudoRand($colonyId * 9137 + $tick * 251, 0, 999);
        if ($roll >= (int) round($chance * 1000)) {
            return;
        }

        $split = (float) config('game.bar.information_pool.character_split', 0.5);
        $splitRoll = $this->pseudoRand($colonyId * 6199 + $tick * 271, 0, 999);
        $slug = $splitRoll < (int) round($split * 1000) ? 'veteran' : 'ai_researcher';

        $available = $this->availableInformationOutcomes($colonyId, $slug);
        $outcomeKey = $available[$this->pseudoRand($colonyId * 8293 + $tick * 293, 0, count($available) - 1)];

        $duration = (int) config('game.bar.information_pool.offer_duration', 2);

        BarInformationEncounter::create([
            'colony_id' => $colonyId,
            'character_slug' => $slug,
            'outcome_key' => $outcomeKey,
            'created_tick' => $tick,
            'expires_tick' => $tick + $duration,
            'is_resolved' => false,
        ]);
    }

    /**
     * The 4 possible outcome keys for a given character, filtered by the
     * once-per-run mechanical cap (colony_information_pool_state.*_used) —
     * the 2 narrative outcomes are always included, unlimited. See
     * config/game.php → bar.information_pool for the "Deckel-Regel" this
     * implements: as long as at least 1 mechanical outcome for the figure is
     * still unused, the pool mixes mechanical+narrative; once both are used,
     * only the narrative outcomes remain.
     *
     * @return string[]
     */
    private function availableInformationOutcomes(int $colonyId, string $slug): array
    {
        $state = DB::table('colony_information_pool_state')->where('colony_id', $colonyId)->first();

        $mechanicalKeys = $slug === 'veteran'
            ? ['drill_buffer' => 'deva_buff_used', 'knowledge_boost' => 'deva_knowledge_used']
            : ['nav_discount' => 'lenn_nav_used', 'knowledge_boost' => 'lenn_knowledge_used'];

        $available = [];
        foreach ($mechanicalKeys as $outcomeKey => $usedField) {
            if (! ($state->{$usedField} ?? false)) {
                $available[] = $outcomeKey;
            }
        }

        $available[] = 'narrative_1';
        $available[] = 'narrative_2';

        return $available;
    }

    public function getActiveInformationEncounter(int $colonyId, int $tick): ?BarInformationEncounter
    {
        return BarInformationEncounter::where('colony_id', $colonyId)
            ->where('is_resolved', false)
            ->where('expires_tick', '>', $tick)
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Resolves a Deva & Lenn Vier-Ausgänge-Pool encounter (A42). No AP cost —
     * "mit Deva/Lenn reden" is a free tactical exchange, mirroring Tomas'
     * bartender interaction (A40), not a priced Cantina action. Every
     * resolution (mechanical or narrative) records Charakter-Kodex progress
     * for the figure (GDD §12 "Charakter-Kodex", A42).
     *
     * @param  int|null  $knowledgeId  Only consulted for veteran's
     *                                 'knowledge_boost' outcome — the player's
     *                                 choice between the two allowed knowledge
     *                                 IDs (config('game.bar.information_pool.
     *                                 veteran.knowledge_choices')). Ignored for
     *                                 every other outcome/character.
     */
    public function resolveInformationEncounter(int $colonyId, int $encounterId, int $userId, int $currentTick, ?int $knowledgeId = null): array
    {
        $encounter = BarInformationEncounter::where('id', $encounterId)
            ->where('colony_id', $colonyId)
            ->first();

        if (! $encounter) {
            return ['ok' => false, 'error' => __('colony.bar_information_not_found')];
        }
        if ($encounter->is_resolved) {
            return ['ok' => false, 'error' => __('colony.bar_information_already_resolved')];
        }
        if ($encounter->expires_tick <= $currentTick) {
            return ['ok' => false, 'error' => __('colony.bar_information_expired')];
        }

        return DB::transaction(function () use ($encounter, $colonyId, $userId, $knowledgeId): array {
            $outcome = $this->resolveInformationOutcome($colonyId, $encounter->character_slug, $encounter->outcome_key, $knowledgeId);

            $encounter->is_resolved = true;
            $encounter->outcome = $outcome;
            $encounter->save();

            // Charakter-Kodex (A42): every resolved encounter from this pool
            // counts, mechanical or narrative alike.
            $this->characterCodexService->recordProgress($userId, $encounter->character_slug);

            Log::info('bar_information_resolved', [
                'colony_id' => $colonyId,
                'encounter_id' => $encounter->id,
                'character_slug' => $encounter->character_slug,
                'outcome_key' => $encounter->outcome_key,
                'outcome' => $outcome,
            ]);

            return array_merge([
                'ok' => true,
                'character_slug' => $encounter->character_slug,
                'outcome_key' => $encounter->outcome_key,
            ], $outcome);
        });
    }

    /**
     * Executes the already-drawn outcome's effect. Runs inside the caller's
     * DB transaction.
     */
    private function resolveInformationOutcome(int $colonyId, string $slug, string $outcomeKey, ?int $knowledgeId): array
    {
        return match ([$slug, $outcomeKey]) {
            ['veteran', 'drill_buffer'] => $this->resolveDrillBufferOutcome($colonyId),
            ['veteran', 'knowledge_boost'] => $this->resolveVeteranKnowledgeBoostOutcome($colonyId, $knowledgeId),
            ['ai_researcher', 'nav_discount'] => $this->resolveNavDiscountOutcome($colonyId),
            ['ai_researcher', 'knowledge_boost'] => $this->resolveAiResearcherKnowledgeBoostOutcome($colonyId),
            default => ['reward' => 'narrative'], // narrative_1/narrative_2 — flavor only
        };
    }

    private function ensureInformationPoolStateRow(int $colonyId): void
    {
        DB::table('colony_information_pool_state')->insertOrIgnore(['colony_id' => $colonyId]);
    }

    private function resolveDrillBufferOutcome(int $colonyId): array
    {
        $this->ensureInformationPoolStateRow($colonyId);
        DB::table('colony_information_pool_state')
            ->where('colony_id', $colonyId)
            ->update(['active_drill_buffer' => true, 'deva_buff_used' => true]);

        return ['reward' => 'drill_buffer'];
    }

    private function resolveVeteranKnowledgeBoostOutcome(int $colonyId, ?int $knowledgeId): array
    {
        $apBonus = (int) config('game.bar.information_pool.veteran.knowledge_boost_ap', 0);
        $choices = config('game.bar.information_pool.veteran.knowledge_choices', []);
        $allowedIds = array_map(fn ($key) => (int) config("knowledge.{$key}.id"), $choices);

        if ($knowledgeId === null || ! in_array($knowledgeId, $allowedIds, true)) {
            return ['reward' => 'knowledge_boost', 'ap_bonus' => 0, 'error' => 'knowledge_id_required'];
        }

        $this->researchService->investBonus($colonyId, $knowledgeId, $apBonus);

        $this->ensureInformationPoolStateRow($colonyId);
        DB::table('colony_information_pool_state')
            ->where('colony_id', $colonyId)
            ->update(['deva_knowledge_used' => true]);

        return ['reward' => 'knowledge_boost', 'ap_bonus' => $apBonus, 'knowledge_id' => $knowledgeId];
    }

    private function resolveNavDiscountOutcome(int $colonyId): array
    {
        $this->ensureInformationPoolStateRow($colonyId);
        DB::table('colony_information_pool_state')
            ->where('colony_id', $colonyId)
            ->update(['active_nav_voucher' => true, 'lenn_nav_used' => true]);

        return ['reward' => 'nav_discount'];
    }

    private function resolveAiResearcherKnowledgeBoostOutcome(int $colonyId): array
    {
        $apBonus = (int) config('game.bar.information_pool.ai_researcher.knowledge_boost_ap', 0);
        $target = config('game.bar.information_pool.ai_researcher.knowledge_target', 'cartography');
        $knowledgeId = (int) config("knowledge.{$target}.id");

        $this->researchService->investBonus($colonyId, $knowledgeId, $apBonus);

        $this->ensureInformationPoolStateRow($colonyId);
        DB::table('colony_information_pool_state')
            ->where('colony_id', $colonyId)
            ->update(['lenn_knowledge_used' => true]);

        return ['reward' => 'knowledge_boost', 'ap_bonus' => $apBonus, 'knowledge_id' => $knowledgeId];
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
        // Base price only — the Konsul's Handelsvorteil raises the goods at accept time.
        $unitPrice = max(0.01, (float) $rawPrice);

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
        return $this->tradeAdvantageService->consulRank($colonyId);
    }

    /**
     * "Mit Tomas reden" (Cantina-Barkeeper, A40, GDD §12). Costs no AP and never
     * locks or checks the shared colony pool — Tomas injects AP directly into the
     * chosen knowledge via ResearchService::investBonus(). Gated by a once-per-tick cooldown;
     * the bonus tier is resolved from the colony's cumulative, run-persistent
     * interaction_count as it stood BEFORE this interaction, then the counter
     * is incremented regardless of whether a bonus tier was reached.
     *
     * @return array{success: bool, ap_added?: int, interaction_count?: int, error?: string}
     */
    public function talkToBartender(int $colonyId, int $knowledgeId, int $currentTick): array
    {
        $state = DB::table('colony_bartender_state')->where('colony_id', $colonyId)->first();

        $cooldownTicks = (int) config('game.bartender.interaction_cooldown_ticks');
        $lastInteractionTick = $state?->last_interaction_tick;

        if ($lastInteractionTick !== null && $currentTick - $lastInteractionTick < $cooldownTicks) {
            return ['success' => false, 'error' => 'cooldown_active'];
        }

        $countBefore = $state->interaction_count ?? 0;
        $apAdded = $this->bartenderApBonus($countBefore);

        return DB::transaction(function () use ($colonyId, $knowledgeId, $currentTick, $countBefore, $apAdded): array {
            DB::table('colony_bartender_state')->updateOrInsert(
                ['colony_id' => $colonyId],
                ['interaction_count' => $countBefore + 1, 'last_interaction_tick' => $currentTick],
            );

            if ($apAdded > 0) {
                // Tomas' bonus AP is earmarked, not drawn from the shared colony pool —
                // investBonus() skips that pool's availability gate so the bonus can
                // never silently evaporate because the pool happens to be near-empty.
                $this->researchService->investBonus($colonyId, $knowledgeId, $apAdded);
            }

            return [
                'success' => true,
                'ap_added' => $apAdded,
                'interaction_count' => $countBefore + 1,
            ];
        });
    }

    /** AP bonus for the given interaction_count, per config/game.php → bartender.ap_bonus_tiers. */
    private function bartenderApBonus(int $interactionCount): int
    {
        $tiers = config('game.bartender.ap_bonus_tiers', []);
        $bonus = 0;

        foreach ($tiers as $threshold => $tierBonus) {
            if ($interactionCount >= $threshold) {
                $bonus = $tierBonus;
            }
        }

        return $bonus;
    }

    /** Barter: player gives one resource, gets another (no credits involved). */
    private function buildBarterOffer(int $seed, array $basePrices): array
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
        $getAmount = (int) max(1, round($givePrice / $getPrice));

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
