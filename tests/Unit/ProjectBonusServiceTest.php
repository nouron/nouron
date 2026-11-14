<?php

namespace Tests\Unit;

use App\Services\ProjectBonusService;
use App\Services\TickService;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProjectBonusServiceTest extends TestCase
{
    use RefreshDatabase;

    private const COLONY_ID = 1;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
    }

    private function setKnowledgeLevel(int $researchId, int $level): void
    {
        DB::table('colony_researches')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'research_id' => $researchId],
            ['level' => $level, 'ap_spend' => 0, 'status_points' => 20]
        );
    }

    public function test_discount_is_zero_with_no_knowledge_invested(): void
    {
        $service = $this->app->make(ProjectBonusService::class);

        $this->assertSame(0, $service->buildingApDiscount(self::COLONY_ID));
    }

    public function test_building_discount_comes_from_construction_only(): void
    {
        // construction id=90 is the sole source of the building-project discount.
        $this->setKnowledgeLevel(90, 4);   // cumulative absolute AP [2 => 1, 4 => 1] = 2
        $service = $this->app->make(ProjectBonusService::class);

        $this->assertSame(2, $service->buildingApDiscount(self::COLONY_ID));
    }

    public function test_trade_no_longer_contributes_to_building_discount(): void
    {
        // Owner-Entscheidung 2026-09-20 (A13): building level-up AP costs are not a
        // trade matter — thematically wrong, trade acts on prices/offers/negotiation.
        $this->setKnowledgeLevel(95, 5);   // trade Lv5 — voll investiert
        $service = $this->app->make(ProjectBonusService::class);

        $this->assertSame(0, $service->buildingApDiscount(self::COLONY_ID), 'trade must not feed the building-project discount pool');
        $this->assertArrayNotHasKey('ap_cost_reduction_per_lv', config('knowledge.trade'));
    }

    public function test_cartography_no_longer_contributes_to_building_discount(): void
    {
        $this->setKnowledgeLevel(91, 5);   // cartography Lv5 — voll investiert
        $service = $this->app->make(ProjectBonusService::class);

        $this->assertSame(0, $service->buildingApDiscount(self::COLONY_ID), 'cartography must no longer feed the building-project discount pool');
    }

    public function test_effective_ap_for_levelup_applies_discount(): void
    {
        $this->setKnowledgeLevel(90, 5);   // construction Lv5 = 2 AP (absolute)
        $service = $this->app->make(ProjectBonusService::class);

        // A Command Center levelup (16 AP) is discountable, a 4-AP Ausbaustufe is not (§5.1.2).
        $this->assertSame(14, $service->effectiveApForLevelup(self::COLONY_ID, 16));
        $this->assertSame(4, $service->effectiveApForLevelup(self::COLONY_ID, 4));
    }

    // ── Analytik-Labor Domänen-Effizienzbonus "Wissen" (Design-Spec 2026-08-23) ──

    private const SCIENCELAB_ID = 31;

    private function setSciencelabLevel(int $level): void
    {
        DB::table('colony_buildings')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'building_id' => self::SCIENCELAB_ID],
            ['level' => $level, 'status_points' => 20, 'ap_spend' => 0]
        );
    }

    public function test_knowledge_discount_is_zero_at_level_1(): void
    {
        $this->setSciencelabLevel(1);
        $service = $this->app->make(ProjectBonusService::class);

        $this->assertSame(0, $service->knowledgeApDiscount(self::COLONY_ID), 'Lv1 is a pure Kenntnis-gate, no discount yet');
    }

    public function test_knowledge_discount_at_level_3(): void
    {
        $this->setSciencelabLevel(3);
        $service = $this->app->make(ProjectBonusService::class);

        $expected = (int) (config('buildings.sciencelab.knowledge_ap_cost_reduction_per_lv')[3] ?? 0);
        $this->assertGreaterThan(0, $expected, 'precondition: config must define a Lv3 discount');
        $this->assertSame($expected, $service->knowledgeApDiscount(self::COLONY_ID));
    }

    public function test_knowledge_discount_at_level_5_is_cumulative(): void
    {
        $this->setSciencelabLevel(5);
        $service = $this->app->make(ProjectBonusService::class);

        $curve = config('buildings.sciencelab.knowledge_ap_cost_reduction_per_lv');
        $expected = (int) array_sum(array_intersect_key($curve, array_flip(range(1, 5))));
        $this->assertSame($expected, $service->knowledgeApDiscount(self::COLONY_ID));
    }

    public function test_effective_knowledge_ap_for_levelup_applies_discount(): void
    {
        $this->setSciencelabLevel(5);
        $service = $this->app->make(ProjectBonusService::class);

        $discount = $service->knowledgeApDiscount(self::COLONY_ID);
        $this->assertSame(21 - $discount, $service->effectiveKnowledgeApForLevelup(self::COLONY_ID, 21));
    }

    public function test_knowledge_discount_does_not_affect_building_discount_pool(): void
    {
        // Die beiden Pools sind unabhängig — Analytik-Labor-Level darf den
        // bestehenden Gebäude-Rabatt (construction) nicht
        // beeinflussen, und umgekehrt.
        $this->setSciencelabLevel(5);
        $this->setKnowledgeLevel(90, 0); // construction unbelegt
        $service = $this->app->make(ProjectBonusService::class);

        $this->assertSame(0, $service->buildingApDiscount(self::COLONY_ID));
    }

    // ── trade Handelspreis-Bonus (Owner-Entscheidung 2026-08-27) ────────────────

    private const TRADE_RESEARCH_ID = 95;

    public function test_trade_price_bonus_percent_sums_curve_up_to_level(): void
    {
        $this->assertSame((int) config('knowledge.trade.id'), self::TRADE_RESEARCH_ID, 'precondition: config/knowledge.php trade.id must match the test constant');
        $this->setKnowledgeLevel(self::TRADE_RESEARCH_ID, 3);
        $service = $this->app->make(ProjectBonusService::class);

        // curve [1=>2,2=>3,3=>3,...] summed to level 3 = 8
        $this->assertSame(8, $service->tradePriceBonusPercent(self::COLONY_ID));
    }

    public function test_trade_price_bonus_percent_is_zero_with_no_trade_knowledge(): void
    {
        $service = $this->app->make(ProjectBonusService::class);

        $this->assertSame(0, $service->tradePriceBonusPercent(self::COLONY_ID));
    }

    // ── Cantina-Anliegen Baukosten-Rabatt-Gutschein (A41) ───────────────────────

    private function insertVoucher(int $discountAp, string $source, ?int $expiresTick = null, ?int $consumedTick = null): int
    {
        return DB::table('colony_building_discount_vouchers')->insertGetId([
            'colony_id' => self::COLONY_ID,
            'discount_pct' => $discountAp,
            'source' => $source,
            'granted_tick' => 1,
            'expires_tick' => $expiresTick,
            'consumed_tick' => $consumedTick,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_active_voucher_ap_is_zero_with_no_voucher(): void
    {
        $service = $this->app->make(ProjectBonusService::class);

        $this->assertSame(0, $service->activeBuildingDiscountVoucherAp(self::COLONY_ID));
    }

    public function test_active_voucher_ap_returns_unconsumed_unexpired_voucher(): void
    {
        $this->insertVoucher(2, 'information_broker');
        $service = $this->app->make(ProjectBonusService::class);

        $this->assertSame(2, $service->activeBuildingDiscountVoucherAp(self::COLONY_ID));
    }

    public function test_active_voucher_ap_ignores_consumed_voucher(): void
    {
        $this->insertVoucher(2, 'information_broker', consumedTick: 5);
        $service = $this->app->make(ProjectBonusService::class);

        $this->assertSame(0, $service->activeBuildingDiscountVoucherAp(self::COLONY_ID));
    }

    public function test_active_voucher_ap_ignores_expired_voucher(): void
    {
        $this->app->instance(TickService::class, new TickService(20));
        $this->insertVoucher(1, 'founder', expiresTick: 10);
        $service = $this->app->make(ProjectBonusService::class);

        $this->assertSame(0, $service->activeBuildingDiscountVoucherAp(self::COLONY_ID));
    }

    public function test_active_voucher_ap_picks_the_larger_discount_when_two_are_active(): void
    {
        $this->insertVoucher(1, 'founder');
        $this->insertVoucher(2, 'information_broker');
        $service = $this->app->make(ProjectBonusService::class);

        $this->assertSame(2, $service->activeBuildingDiscountVoucherAp(self::COLONY_ID));
    }

    public function test_effective_ap_for_levelup_folds_in_active_voucher(): void
    {
        $this->insertVoucher(2, 'information_broker');
        $service = $this->app->make(ProjectBonusService::class);

        // No knowledge discount, only the 2-AP voucher: 8 - 2 = 6.
        $this->assertSame(6, $service->effectiveApForLevelup(self::COLONY_ID, 8));
    }

    public function test_consume_active_building_discount_voucher_marks_it_consumed(): void
    {
        $id = $this->insertVoucher(2, 'information_broker');
        $this->app->instance(TickService::class, new TickService(7));
        $service = $this->app->make(ProjectBonusService::class);

        $service->consumeActiveBuildingDiscountVoucher(self::COLONY_ID);

        $this->assertSame(7, DB::table('colony_building_discount_vouchers')->where('id', $id)->value('consumed_tick'));
        $this->assertSame(0, $service->activeBuildingDiscountVoucherAp(self::COLONY_ID));
    }

    public function test_consume_active_building_discount_voucher_is_noop_without_active_voucher(): void
    {
        $service = $this->app->make(ProjectBonusService::class);

        $service->consumeActiveBuildingDiscountVoucher(self::COLONY_ID);

        $this->assertSame(0, DB::table('colony_building_discount_vouchers')->count());
    }
}
