<?php

namespace Tests\Feature\Colony;

use App\Models\User;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Building level-up — POST colony/building/invest.
 *
 * Rules:
 * - Each invest adds 1 construction AP towards ap_for_levelup.
 * - On reaching the threshold: level +1, ap_spend resets to 0, and
 *   status_points is restored to max_status_points.
 * - Investing past max_level is rejected.
 *
 * Fixture: Colony 1 (Springfield), user_id=3 (Bart), CC building_id=25
 * (ap_for_levelup=10, max_level=5, max_status_points=20).
 */
class BuildingInvestTest extends TestCase
{
    use RefreshDatabase;

    private const COLONY_ID = 1;

    private const BART_USER_ID = 3;

    private const CC_ID = 25;

    private const CC_AP_FOR_LEVELUP = 8;   // cc_upgrade_ap_by_level[2] (config/buildings.php)

    private const CC_MAX_SP = 20;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();

        // Agrardom gate (ROADMAP C16): CC Lv1 -> Lv2 requires an Agrardom already
        // placed. This suite is about the invest/level-up mechanics, not that gate —
        // pre-place it so the fixture matches the intended "Agrardom first" Sol-1 flow.
        DB::table('colony_buildings')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'building_id' => 41, 'instance_id' => 1],
            ['level' => 1, 'status_points' => 20, 'ap_spend' => 0, 'tile_x' => 2, 'tile_y' => 0]
        );
    }

    private function makeUser(int $userId): User
    {
        return User::where('user_id', $userId)->firstOrFail();
    }

    private function setCcState(array $attrs): void
    {
        DB::table('colony_buildings')
            ->where('colony_id', self::COLONY_ID)
            ->where('building_id', self::CC_ID)
            ->update($attrs);
    }

    private function ccRow(): object
    {
        return DB::table('colony_buildings')
            ->where('colony_id', self::COLONY_ID)
            ->where('building_id', self::CC_ID)
            ->first();
    }

    private function invest(int $buildingId = self::CC_ID, ?int $ap = null)
    {
        return $this->actingAs($this->makeUser(self::BART_USER_ID))
            ->postJson(route('colony.building.invest'), array_filter([
                'building_id' => $buildingId,
                'ap' => $ap,
            ]));
    }

    public function test_invest_increments_ap_progress_without_levelup(): void
    {
        $this->setCcState(['level' => 1, 'ap_spend' => 0]);

        $response = $this->invest();

        $response->assertOk()->assertJsonPath('ok', true);
        $response->assertJsonPath('leveled_up', false);
        $this->assertSame(1, (int) $this->ccRow()->ap_spend);
        $this->assertSame(1, (int) $this->ccRow()->level);
    }

    public function test_invest_with_ap_param_spends_several_ap_in_one_request(): void
    {
        $this->setCcState(['level' => 1, 'ap_spend' => 0]);

        // Two AP fit into one Sol (building cap 1 + engineer field bonus).
        $response = $this->invest(self::CC_ID, 2);

        $response->assertOk()->assertJsonPath('ok', true)->assertJsonPath('applied', 2);
        $this->assertSame(2, (int) $this->ccRow()->ap_spend);
    }

    public function test_invest_batch_stops_at_levelup_without_overshoot(): void
    {
        $this->setCcState(['level' => 1, 'ap_spend' => self::CC_AP_FOR_LEVELUP - 2]);

        $response = $this->invest(self::CC_ID, 5);

        $response->assertOk()->assertJsonPath('leveled_up', true)->assertJsonPath('applied', 2);
        $row = $this->ccRow();
        $this->assertSame(2, (int) $row->level);
        $this->assertSame(0, (int) $row->ap_spend, 'the 3 surplus AP must not start the next cycle');
    }

    public function test_invest_batch_returns_partial_success_when_a_gate_closes(): void
    {
        $this->setCcState(['level' => 1, 'ap_spend' => 0]);

        // Far more than one Sol allows (per-building AP cap / available AP): the batch applies what
        // fits, stops at the first rejected step and still answers ok.
        $response = $this->invest(self::CC_ID, 20);

        $response->assertOk()->assertJsonPath('ok', true);
        $applied = (int) $response->json('applied');
        $this->assertGreaterThanOrEqual(1, $applied);
        $this->assertLessThan(20, $applied);
        $this->assertNotNull($response->json('stopped_by'));
        $this->assertSame($applied, (int) $this->ccRow()->ap_spend);
    }

    public function test_invest_levels_up_at_threshold(): void
    {
        // One invest away from the threshold so a single AP completes the level.
        $this->setCcState([
            'level' => 1,
            'ap_spend' => self::CC_AP_FOR_LEVELUP - 1,
            'status_points' => 16,
        ]);

        $response = $this->invest();

        $response->assertOk()->assertJsonPath('ok', true);
        $response->assertJsonPath('leveled_up', true);

        $row = $this->ccRow();
        $this->assertSame(2, (int) $row->level, 'Level must increment');
        $this->assertSame(0, (int) $row->ap_spend, 'ap_spend resets after level-up');
        $this->assertSame(self::CC_MAX_SP, (int) $row->status_points, 'Status restored to max on level-up');
    }

    public function test_invest_rejected_at_max_level(): void
    {
        $this->setCcState(['level' => 5, 'ap_spend' => 0]);

        $response = $this->invest();

        $response->assertStatus(422)->assertJsonPath('ok', false);
        $this->assertSame('max_level_reached', $response->json('error'));
        $this->assertSame(__('colony.error_max_level_reached'), $response->json('message'));
        $this->assertSame(5, (int) $this->ccRow()->level);
    }

    public function test_invest_writes_protocol_event(): void
    {
        $this->setCcState(['level' => 1, 'ap_spend' => 0]);

        $this->invest()->assertJsonPath('ok', true);

        $this->assertDatabaseHas('colony_log', [
            'user' => self::BART_USER_ID,
            'event' => 'colony.building_invested',
        ]);
    }

    // ── Cantina-Anliegen Baukosten-Rabatt-Gutschein (A41) ───────────────────────

    private function insertVoucher(int $discountAp, string $source): void
    {
        DB::table('colony_building_discount_vouchers')->insert([
            'colony_id' => self::COLONY_ID,
            'discount_pct' => $discountAp,
            'source' => $source,
            'granted_tick' => 1,
            'expires_tick' => null,
            'consumed_tick' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_invest_reaches_levelup_earlier_with_an_active_voucher(): void
    {
        // 2 AP off CC Lv1->2 (8 AP, absolute since the AP scale) -> effective threshold 6.
        $this->insertVoucher(2, 'information_broker');
        $this->setCcState(['level' => 1, 'ap_spend' => 5, 'status_points' => 16]);

        $response = $this->invest();

        $response->assertOk()->assertJsonPath('leveled_up', true);
        $this->assertSame(2, (int) $this->ccRow()->level);
    }

    public function test_invest_levelup_consumes_the_active_voucher(): void
    {
        $this->insertVoucher(2, 'information_broker');
        $this->setCcState(['level' => 1, 'ap_spend' => 5, 'status_points' => 16]);

        $this->invest()->assertJsonPath('leveled_up', true);

        $voucher = DB::table('colony_building_discount_vouchers')->where('colony_id', self::COLONY_ID)->first();
        $this->assertNotNull($voucher->consumed_tick, 'voucher must be marked consumed once the level-up it discounted completes');
    }

    public function test_invest_without_levelup_does_not_consume_the_voucher(): void
    {
        $this->insertVoucher(2, 'information_broker');
        $this->setCcState(['level' => 1, 'ap_spend' => 0, 'status_points' => 16]);

        $this->invest()->assertJsonPath('leveled_up', false);

        $voucher = DB::table('colony_building_discount_vouchers')->where('colony_id', self::COLONY_ID)->first();
        $this->assertNull($voucher->consumed_tick, 'voucher must remain active until a level-up actually completes');
    }
}
