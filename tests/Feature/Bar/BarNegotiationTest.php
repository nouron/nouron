<?php

namespace Tests\Feature\Bar;

/**
 * Cantina-Verhandlung neu (GDD §12, A13/P3):
 *  - AP cost equals Annehmen (2/2), paid on success AND failure; Annehmen after a
 *    success costs 0 AP,
 *  - success chance = rank base + trade knowledge (percentage points, cumulative),
 *    silently capped; ONE method (BarService::negotiateChance()) feeds roll and dialog,
 *  - success only sets the is_negotiated flag — nothing is frozen into the stored
 *    amounts; the Get side is base x (1 + Handelsvorteil + negotiation bonus), additive,
 *    the Give side (price) never moves,
 *  - the Handelsposten still counts on negotiated offers,
 *  - failure: offer gone, Give stays with the player, AP spent, the result carries the facts,
 *  - calibration: from the REAL config and services, negotiating is neither always
 *    better nor always worse than a plain Annehmen (design intent A13).
 */

use App\Services\BarService;
use App\Services\TickService;
use App\Services\TradeAdvantageService;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BarNegotiationTest extends TestCase
{
    use RefreshDatabase;

    private const COLONY_ID = 1;

    private const USER_ID = 3;

    private const TRADING_POST_ID = 55;

    private const TRADER_ADVISOR_ID = 92;

    private const RES_CREDITS = 1;

    private const RES_REGOLITH = 3;

    private const RES_COMPOUNDS = 4;

    private const RES_ORGANICS = 5;

    private BarService $barService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
        $this->mockTick(10);
        $this->barService = $this->app->make(BarService::class);
        DB::table('bar_offers')->where('colony_id', self::COLONY_ID)->delete();
        DB::table('advisors')->where('colony_id', self::COLONY_ID)->where('personell_id', self::TRADER_ADVISOR_ID)->delete();
        $this->setTradingPostLevel(0);
        $this->setTradeLevel(0);
        config(['game.bypass.ap_checks' => true]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function mockTick(int $tick): void
    {
        $this->app->instance(TickService::class, new TickService($tick));
    }

    private function assignConsul(int $rank): void
    {
        DB::table('advisors')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'personell_id' => self::TRADER_ADVISOR_ID],
            ['rank' => $rank, 'user_id' => self::USER_ID, 'active_ticks' => 0, 'unavailable_until_tick' => null]
        );
    }

    private function setTradingPostLevel(int $level): void
    {
        DB::table('colony_buildings')->where('colony_id', self::COLONY_ID)->where('building_id', self::TRADING_POST_ID)->delete();
        if ($level > 0) {
            DB::table('colony_buildings')->insert([
                'colony_id' => self::COLONY_ID, 'building_id' => self::TRADING_POST_ID, 'instance_id' => 1,
                'level' => $level, 'status_points' => 20, 'ap_spend' => 0,
            ]);
        }
    }

    private function setTradeLevel(int $level): void
    {
        DB::table('colony_researches')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'research_id' => (int) config('knowledge.trade.id')],
            ['level' => $level, 'ap_spend' => 0, 'status_points' => 20]
        );
    }

    private function setColonyResource(int $resourceId, int $amount): void
    {
        DB::table('colony_resources')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'resource_id' => $resourceId],
            ['amount' => $amount]
        );
    }

    private function colonyResource(int $resourceId): int
    {
        return (int) DB::table('colony_resources')
            ->where('colony_id', self::COLONY_ID)->where('resource_id', $resourceId)->value('amount');
    }

    private function setCredits(int $amount): void
    {
        DB::table('user_resources')->where('user_id', self::USER_ID)->update(['credits' => $amount]);
    }

    private function credits(): int
    {
        return (int) DB::table('user_resources')->where('user_id', self::USER_ID)->value('credits');
    }

    private function lockedAp(): int
    {
        return (int) DB::table('locked_actionpoints')->sum('spend_ap');
    }

    private function insertVisit(): int
    {
        return DB::table('merchant_visits')->insertGetId([
            'colony_id' => self::COLONY_ID, 'tick_start' => 10, 'tick_end' => 11,
            'was_visited' => false, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function insertOffer(array $overrides = []): int
    {
        return DB::table('bar_offers')->insertGetId(array_merge([
            'colony_id' => self::COLONY_ID,
            'give_resource_id' => self::RES_REGOLITH,
            'give_amount' => 20,
            'get_resource_id' => self::RES_COMPOUNDS,
            'get_amount' => 10,
            'expires_tick' => 20,
            'is_accepted' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides));
    }

    /** Force the dice: chance 100 % (win) or 0 % (loss) for every rank, guard rail lifted. */
    private function forceOutcome(bool $success): void
    {
        $chance = $success ? 1.0 : 0.0;
        config([
            'game.bar.trade_terms.negotiate_chance_max' => 1.0,
            'game.bar.negotiate_success_chance' => [0 => 0.0, 1 => $chance, 2 => $chance, 3 => $chance],
        ]);
        // A losing roll needs total = 0 even with trade knowledge: no trade level in those tests.
    }

    // ── Config ────────────────────────────────────────────────────────────────

    public function test_negotiate_config_values_match_the_owner_decision(): void
    {
        $this->assertSame(2, (int) config('game.bar.ap_cost_negotiate'));
        $this->assertSame((int) config('game.bar.ap_cost_accept'), (int) config('game.bar.ap_cost_negotiate'), 'Verhandeln costs the same AP as Annehmen');
        $this->assertEquals([0 => 0.0, 1 => 0.60, 2 => 0.65, 3 => 0.70], config('game.bar.negotiate_success_chance'));
        $this->assertEquals([0 => 0.0, 1 => 0.20, 2 => 0.20, 3 => 0.20], config('game.bar.negotiate_bonus'));
        $this->assertEquals(0.95, config('game.bar.trade_terms.negotiate_chance_max'));
        $this->assertSame([1 => 1, 2 => 2, 3 => 2, 4 => 2, 5 => 1], config('knowledge.trade.negotiate_chance_bonus_per_lv'));
    }

    // ── Success chance: one method, sources separated ─────────────────────────

    public function test_negotiate_chance_per_rank_without_knowledge(): void
    {
        $expected = [0 => 0.0, 1 => 0.60, 2 => 0.65, 3 => 0.70];

        foreach ($expected as $rank => $chance) {
            if ($rank > 0) {
                $this->assignConsul($rank);
            }
            $result = $this->barService->negotiateChance(self::COLONY_ID);

            $this->assertSame($rank, $result['rank']);
            $this->assertEqualsWithDelta($chance, $result['base'], 1e-9, "base chance of rank {$rank}");
            $this->assertEqualsWithDelta(0.0, $result['knowledge_bonus'], 1e-9);
            $this->assertEqualsWithDelta($chance, $result['total'], 1e-9);
            $this->assertFalse($result['capped']);
        }
    }

    public function test_negotiate_chance_adds_trade_knowledge_cumulatively_in_percentage_points(): void
    {
        $this->assignConsul(2);
        $cumulativePp = [0 => 0, 1 => 1, 2 => 3, 3 => 5, 4 => 7, 5 => 8];

        foreach ($cumulativePp as $level => $pp) {
            $this->setTradeLevel($level);
            $result = $this->barService->negotiateChance(self::COLONY_ID);

            $this->assertEqualsWithDelta($pp / 100, $result['knowledge_bonus'], 1e-9, "trade Lv{$level}");
            $this->assertSame($pp, $result['knowledge_bonus_percent']);
            $this->assertEqualsWithDelta(0.65 + $pp / 100, $result['total'], 1e-9);
            $this->assertSame(65, $result['base_percent']);
            $this->assertSame(65 + $pp, $result['total_percent']);
        }
    }

    public function test_negotiate_chance_is_capped_by_the_silent_guard_rail(): void
    {
        config(['game.bar.negotiate_success_chance' => [0 => 0.0, 1 => 0.60, 2 => 0.65, 3 => 0.90]]);
        $this->assignConsul(3);
        $this->setTradeLevel(5); // 0.90 + 0.08 = 0.98 -> capped at 0.95

        $result = $this->barService->negotiateChance(self::COLONY_ID);

        $this->assertEqualsWithDelta(0.95, $result['total'], 1e-9);
        $this->assertSame(95, $result['total_percent']);
        $this->assertTrue($result['capped']);
    }

    public function test_negotiate_chance_is_zero_without_a_consul_even_with_trade_knowledge(): void
    {
        $this->setTradeLevel(5);

        $result = $this->barService->negotiateChance(self::COLONY_ID);

        $this->assertSame(0, $result['rank']);
        $this->assertEqualsWithDelta(0.0, $result['total'], 1e-9);
        $this->assertEqualsWithDelta(0.0, $result['knowledge_bonus'], 1e-9, 'trade only helps when a Konsul negotiates');
    }

    public function test_negotiate_chance_is_zero_while_the_consul_is_on_a_mission(): void
    {
        $this->assignConsul(3);
        DB::table('advisors')->where('colony_id', self::COLONY_ID)->where('personell_id', self::TRADER_ADVISOR_ID)
            ->update(['unavailable_until_tick' => 99]);

        $this->assertEqualsWithDelta(0.0, $this->barService->negotiateChance(self::COLONY_ID)['total'], 1e-9);
    }

    public function test_the_roll_uses_the_total_of_negotiate_chance(): void
    {
        // trade Lv5 lifts a config chance of 0.0 to 0.08 -> with an otherwise lost roll
        // the total from negotiateChance() must decide, so over many rolls SOME win.
        config(['game.bar.negotiate_success_chance' => [0 => 0.0, 1 => 0.0, 2 => 0.0, 3 => 0.0]]);
        $this->assignConsul(2);
        $this->setTradeLevel(5);
        $this->setColonyResource(self::RES_REGOLITH, 100000);

        $wins = 0;
        for ($tick = 1; $tick <= 200; $tick++) {
            $this->mockTick($tick);
            $this->barService = $this->app->make(BarService::class);
            DB::table('bar_offers')->where('colony_id', self::COLONY_ID)->delete();
            $offerId = $this->insertOffer(['expires_tick' => $tick + 10]);
            $result = $this->barService->negotiateOffer(self::COLONY_ID, $offerId, self::USER_ID, $tick);
            $wins += $result['success'] ? 1 : 0;
        }

        $this->assertGreaterThan(0, $wins, 'trade knowledge alone must be able to win a roll');
        $this->assertLessThan(60, $wins, '8 % chance -> far from the majority');
    }

    // ── Success: flag only, additive terms ────────────────────────────────────

    public function test_success_sets_only_the_flag_and_writes_no_amounts(): void
    {
        $this->forceOutcome(true);
        $this->assignConsul(3);
        $this->setColonyResource(self::RES_REGOLITH, 1000);
        $offerId = $this->insertOffer(['give_amount' => 30, 'get_amount' => 10]);

        $result = $this->barService->negotiateOffer(self::COLONY_ID, $offerId, self::USER_ID, 10);

        $this->assertTrue($result['ok']);
        $this->assertTrue($result['success']);
        $row = DB::table('bar_offers')->where('id', $offerId)->first();
        $this->assertTrue((bool) $row->is_negotiated);
        $this->assertFalse((bool) $row->is_accepted);
        $this->assertSame(30, (int) $row->give_amount, 'stored Give stays the base');
        $this->assertSame(10, (int) $row->get_amount, 'stored Get stays the base');
        $this->assertSame(1000, $this->colonyResource(self::RES_REGOLITH), 'no resources move on Verhandeln');
    }

    public function test_negotiated_barter_get_side_is_base_times_one_plus_advantage_plus_bonus(): void
    {
        $this->forceOutcome(true);
        $this->assignConsul(3); // +30 %
        $this->setTradingPostLevel(1); // +12 %
        $this->setColonyResource(self::RES_REGOLITH, 1000);
        $this->setColonyResource(self::RES_COMPOUNDS, 0);
        $offerId = $this->insertOffer(['give_amount' => 30, 'get_amount' => 50]);

        $result = $this->barService->negotiateOffer(self::COLONY_ID, $offerId, self::USER_ID, 10);
        $accepted = $this->barService->acceptOffer(self::COLONY_ID, $offerId, self::USER_ID, 10);

        // 50 x (1 + 0.30 + 0.12 + 0.20) = 81 — additive (a multiplicative stack would give 85.2).
        $this->assertSame(81, $result['terms']['get_amount']);
        $this->assertSame(81, $accepted['get_amount']);
        $this->assertSame(30, $accepted['give_amount'], 'the price (Give side) never moves');
        $this->assertSame(81, $this->colonyResource(self::RES_COMPOUNDS));
        $this->assertSame(970, $this->colonyResource(self::RES_REGOLITH));
    }

    public function test_negotiated_corvan_buy_offer_raises_goods_and_keeps_the_price(): void
    {
        $this->forceOutcome(true);
        $this->assignConsul(3); // +30 %
        $this->setCredits(1000);
        $this->setColonyResource(self::RES_REGOLITH, 0);
        $offerId = $this->insertOffer([
            'visit_id' => $this->insertVisit(),
            'give_resource_id' => self::RES_CREDITS, 'give_amount' => 500,
            'get_resource_id' => self::RES_REGOLITH, 'get_amount' => 20,
        ]);

        $result = $this->barService->negotiateOffer(self::COLONY_ID, $offerId, self::USER_ID, 10);

        $this->assertTrue($result['success']);
        $this->assertSame(500, (int) DB::table('bar_offers')->where('id', $offerId)->value('give_amount'), 'no price cut stored');
        $this->assertSame(500, $result['terms']['give_amount'], 'the Credits price no longer drops');
        $this->assertSame(30, $result['terms']['get_amount'], '20 x (1 + 0.30 + 0.20) = 30');

        $accepted = $this->barService->acceptOffer(self::COLONY_ID, $offerId, self::USER_ID, 10);

        $this->assertSame(500, $accepted['give_amount']);
        $this->assertSame(30, $accepted['get_amount']);
        $this->assertSame(500, 1000 - $this->credits());
        $this->assertSame(30, $this->colonyResource(self::RES_REGOLITH));
    }

    public function test_trading_post_still_counts_on_negotiated_offers(): void
    {
        $this->assignConsul(1); // +10 %
        $this->setTradingPostLevel(1); // +12 %
        $offerId = $this->insertOffer(['give_amount' => 20, 'get_amount' => 100, 'is_negotiated' => true]);

        $terms = $this->barService->effectiveTerms(DB::table('bar_offers')->where('id', $offerId)->first(), self::COLONY_ID);

        $sources = array_column($terms['advantage']['sources'], 'value', 'key');
        $this->assertEqualsWithDelta(0.12, $sources[TradeAdvantageService::SOURCE_TRADING_POST], 1e-9);
        $this->assertSame(142, $terms['get_amount'], '100 x (1 + 0.10 + 0.12 + 0.20)');
    }

    public function test_effective_terms_of_an_unnegotiated_offer_carry_no_negotiation_bonus(): void
    {
        $this->assignConsul(2); // +20 %
        $offerId = $this->insertOffer(['give_amount' => 20, 'get_amount' => 50]);

        $terms = $this->barService->effectiveTerms(DB::table('bar_offers')->where('id', $offerId)->first(), self::COLONY_ID);

        $this->assertSame(60, $terms['get_amount'], '50 x 1.20');
        $this->assertEqualsWithDelta(0.0, $terms['negotiation_bonus'], 1e-9);
        $this->assertFalse($terms['negotiated']);
        $this->assertSame(70, $terms['get_amount_if_negotiated'], '50 x (1 + 0.20 + 0.20): what a successful Verhandeln would book');
    }

    public function test_negotiated_offer_keeps_its_bonus_even_if_the_consul_left_afterwards(): void
    {
        // The success was earned; a Konsul on a mission must not silently void it.
        $offerId = $this->insertOffer(['give_amount' => 20, 'get_amount' => 50, 'is_negotiated' => true]);

        $terms = $this->barService->effectiveTerms(DB::table('bar_offers')->where('id', $offerId)->first(), self::COLONY_ID);

        $this->assertSame(60, $terms['get_amount'], '50 x (1 + 0 + 0.20)');
    }

    public function test_negotiation_bonus_is_never_applied_to_a_fixed_price_lot(): void
    {
        $this->assignConsul(3);
        $offerId = $this->insertOffer([
            'visit_id' => $this->insertVisit(),
            'give_resource_id' => self::RES_ORGANICS, 'give_amount' => 20,
            'get_resource_id' => self::RES_CREDITS, 'get_amount' => 700,
            'is_negotiated' => true,
        ]);

        $terms = $this->barService->effectiveTerms(DB::table('bar_offers')->where('id', $offerId)->first(), self::COLONY_ID);

        $this->assertSame(700, $terms['get_amount']);
        $this->assertNull($terms['get_amount_if_negotiated']);
    }

    public function test_negotiate_terms_equal_what_accept_executes_with_every_source_active(): void
    {
        $this->forceOutcome(true);
        $this->assignConsul(3);
        $this->setTradingPostLevel(1);
        $this->setTradeLevel(5);
        $this->setColonyResource(self::RES_REGOLITH, 1000);
        $this->setColonyResource(self::RES_COMPOUNDS, 0);
        $offerId = $this->insertOffer(['give_amount' => 25, 'get_amount' => 17]);

        $result = $this->barService->negotiateOffer(self::COLONY_ID, $offerId, self::USER_ID, 10);
        $accepted = $this->barService->acceptOffer(self::COLONY_ID, $offerId, self::USER_ID, 10);

        $this->assertSame($result['terms']['give_amount'], $accepted['give_amount']);
        $this->assertSame($result['terms']['get_amount'], $accepted['get_amount']);
        $this->assertSame($result['give_amount'], $accepted['give_amount'], 'top-level give == executed');
        $this->assertSame($result['get_amount'], $accepted['get_amount'], 'top-level get == executed');
        // Handelsvorteil 0.30 + 0.12 + 0.12 = 0.54 (under the silent 0.60 bar cap) + 0.20 negotiation: 17 x 1.74 = 29.58 -> 30.
        $this->assertSame(30, $accepted['get_amount']);
    }

    // ── AP ────────────────────────────────────────────────────────────────────

    public function test_negotiate_costs_the_same_ap_as_accept_on_success_and_on_failure(): void
    {
        $this->assignConsul(3);
        $this->setColonyResource(self::RES_REGOLITH, 1000);

        foreach ([true, false] as $success) {
            $this->forceOutcome($success);
            $offerId = $this->insertOffer();
            $before = $this->lockedAp();

            $this->barService->negotiateOffer(self::COLONY_ID, $offerId, self::USER_ID, 10);

            $this->assertSame(
                (int) config('game.bar.ap_cost_accept'),
                $this->lockedAp() - $before,
                'Verhandeln costs '.((int) config('game.bar.ap_cost_accept')).' AP, '.($success ? 'on success' : 'on failure')
            );
        }
    }

    public function test_accept_after_a_successful_negotiation_costs_no_further_ap(): void
    {
        $this->forceOutcome(true);
        $this->assignConsul(2);
        $this->setColonyResource(self::RES_REGOLITH, 1000);
        $offerId = $this->insertOffer();
        $this->barService->negotiateOffer(self::COLONY_ID, $offerId, self::USER_ID, 10);
        $afterNegotiate = $this->lockedAp();

        $result = $this->barService->acceptOffer(self::COLONY_ID, $offerId, self::USER_ID, 10);

        $this->assertTrue($result['ok']);
        $this->assertSame($afterNegotiate, $this->lockedAp(), 'Annehmen of a negotiated offer is free');
    }

    // ── Failure ───────────────────────────────────────────────────────────────

    public function test_failure_removes_the_offer_keeps_the_give_side_and_reports_the_facts(): void
    {
        $this->forceOutcome(false);
        $this->assignConsul(1);
        $this->setColonyResource(self::RES_REGOLITH, 500);
        $this->setColonyResource(self::RES_COMPOUNDS, 7);
        $offerId = $this->insertOffer(['give_amount' => 20, 'get_amount' => 10]);

        $result = $this->barService->negotiateOffer(self::COLONY_ID, $offerId, self::USER_ID, 10);

        $this->assertTrue($result['ok']);
        $this->assertFalse($result['success']);
        $this->assertFalse(DB::table('bar_offers')->where('id', $offerId)->exists(), 'offer is gone for good');
        $this->assertSame(500, $this->colonyResource(self::RES_REGOLITH), 'the Give side stays with the player');
        $this->assertSame(7, $this->colonyResource(self::RES_COMPOUNDS));
        $this->assertSame(self::RES_REGOLITH, $result['give_resource_id'], 'which resource stays with the player');
        $this->assertSame(20, $result['give_amount']);
        $this->assertSame((int) config('game.bar.ap_cost_negotiate'), $result['ap_spent'], 'AP are spent');
        $this->assertArrayNotHasKey('terms', $result);
    }

    public function test_sell_lot_stays_not_negotiable_and_costs_nothing(): void
    {
        $this->forceOutcome(true);
        $this->assignConsul(3);
        $this->setColonyResource(self::RES_ORGANICS, 1000);
        $offerId = $this->insertOffer([
            'visit_id' => $this->insertVisit(),
            'give_resource_id' => self::RES_ORGANICS, 'give_amount' => 20,
            'get_resource_id' => self::RES_CREDITS, 'get_amount' => 700,
        ]);
        $before = $this->lockedAp();

        $result = $this->barService->negotiateOffer(self::COLONY_ID, $offerId, self::USER_ID, 10);

        $this->assertFalse($result['ok']);
        $this->assertSame($before, $this->lockedAp());
        $this->assertTrue(DB::table('bar_offers')->where('id', $offerId)->exists());
    }

    // ── Calibration (design intent A13) ───────────────────────────────────────

    /**
     * Gain of Verhandeln over Annehmen as a share of the Get value V:
     *   g = p x (P + nb + n) - (P + n)
     * P = passive Handelsvorteil, nb = negotiation bonus, n = need premium for the
     * ware lost on failure, p = success chance. Both inputs come from the real
     * config and the real services — nothing is copied as a magic number.
     *
     * @return array{gain: float, p: float, advantage: float}
     */
    private function negotiationGain(float $needPremium): array
    {
        $rank = $this->barService->traderRank(self::COLONY_ID);
        $advantage = $this->app->make(TradeAdvantageService::class)
            ->forChannel(self::COLONY_ID, TradeAdvantageService::CHANNEL_BAR)['total'];
        $nb = (float) config("game.bar.negotiate_bonus.{$rank}");
        $p = $this->barService->negotiateChance(self::COLONY_ID)['total'];

        return [
            'gain' => $p * ($advantage + $nb + $needPremium) - ($advantage + $needPremium),
            'p' => $p,
            'advantage' => $advantage,
        ];
    }

    /** @return array<string, array{0: int, 1: bool, 2: int, 3: bool}> */
    public static function calibrationScenarios(): array
    {
        // name => [consul rank, Handelsposten I, trade level, expected: negotiating beats accepting]
        return [
            'R1 Konsul only' => [1, false, 0, true],
            'R1 + Handelsposten' => [1, true, 0, false],
            'R1 + Handelsposten + trade Lv5' => [1, true, 5, false],
            'R2 Konsul only' => [2, false, 0, true],
            'R2 + Handelsposten' => [2, true, 0, false],
            'R3 Konsul only' => [3, false, 0, true],
            'R3 + Handelsposten' => [3, true, 0, false],
            'R3 + Handelsposten + trade Lv5' => [3, true, 5, true],
        ];
    }

    /** @dataProvider calibrationScenarios */
    public function test_negotiating_is_neither_always_better_nor_always_worse_than_accepting(int $rank, bool $tradingPost, int $tradeLevel, bool $expectedBetter): void
    {
        $this->assignConsul($rank);
        $this->setTradingPostLevel($tradingPost ? 1 : 0);
        $this->setTradeLevel($tradeLevel);
        $needPremium = 0.10; // standard assumption: the ware is worth 10 % more to the player than its market value

        $result = $this->negotiationGain($needPremium);

        $this->assertSame(
            $expectedBetter,
            $result['gain'] > 0,
            sprintf('gain %.4f (p %.2f, passive advantage %.2f) — sign flipped: recalibrate deliberately (GDD §12 Designziel A13)', $result['gain'], $result['p'], $result['advantage'])
        );
    }

    public function test_calibration_leaves_room_for_both_decisions_across_all_ranks(): void
    {
        $better = 0;
        $worse = 0;
        foreach (self::calibrationScenarios() as [$rank, $tradingPost, $tradeLevel]) {
            $this->assignConsul($rank);
            $this->setTradingPostLevel($tradingPost ? 1 : 0);
            $this->setTradeLevel($tradeLevel);
            $gain = $this->negotiationGain(0.10)['gain'];
            $gain > 0 ? $better++ : $worse++;
        }

        $this->assertGreaterThan(0, $better, 'Verhandeln must not be a dead button');
        $this->assertGreaterThan(0, $worse, 'Verhandeln must not be dominant');

        // The best rank must not turn negotiating dominant on its own either (GDD BALANCE CONCERN).
        $this->assignConsul(3);
        $this->setTradingPostLevel(1);
        $this->setTradeLevel(0);
        $this->assertLessThan(0.0, $this->negotiationGain(0.10)['gain'], 'R3 with a Handelsposten prefers the sure Annehmen');
    }
}
