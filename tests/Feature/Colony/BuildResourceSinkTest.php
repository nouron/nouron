<?php

namespace Tests\Feature\Colony;

use App\Models\User;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Resource-sink economy for the hex build flow (PR 1).
 *
 * Rules under test (GDD §3/§4/§6):
 *  - Erecting a building deducts Regolith (+ Werkstoffe on late buildings); CC/Harvester
 *    are bootstrap-exempt. A shortfall is rejected with no DB write.
 *  - Supply is a cap gate (free cap ≥ supply_cost), not a stockpile deduction.
 *  - Level-up deducts flat Regolith (25 % of erect cost; CC scales target_level × 30),
 *    charged only on the completing click — a shortfall never burns the AP.
 *  - Repair deducts 2 Regolith per click (hard gate); CC + Harvester are exempt.
 *  - Nexus compound import: gated by Uplink Lv1, spends Credits, grants Werkstoffe.
 *
 * Fixture: Colony 1 (Springfield), user_id=3 (Bart). Start resources: 250 Regolith (3),
 * 50 Werkstoffe (4). CC building_id=25, depot=30, sciencelab=31, uplinkStation=54.
 */
class BuildResourceSinkTest extends TestCase
{
    use RefreshDatabase;

    private const COLONY_ID = 1;

    private const BART_USER_ID = 3;

    private const RES_REGOLITH = 3;

    private const RES_COMPOUNDS = 4;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();

        // Tests bypass all game checks by default (phpunit.xml). This suite is exactly
        // about the resource + supply gates, so enable them. AP stays bypassed.
        config(['game.bypass.resource_costs' => false, 'game.bypass.supply_checks' => false]);
    }

    private function bart(): User
    {
        return User::where('user_id', self::BART_USER_ID)->firstOrFail();
    }

    private function colonyRes(int $resourceId): int
    {
        return (int) DB::table('colony_resources')
            ->where('colony_id', self::COLONY_ID)->where('resource_id', $resourceId)->value('amount');
    }

    private function setColonyRes(int $resourceId, int $amount): void
    {
        DB::table('colony_resources')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'resource_id' => $resourceId],
            ['amount' => $amount]
        );
    }

    private function credits(): int
    {
        return (int) DB::table('user_resources')->where('user_id', self::BART_USER_ID)->value('credits');
    }

    private function ensureBuildableTile(int $q, int $r): void
    {
        DB::table('colony_tiles')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'q' => $q, 'r' => $r],
            ['ring' => 1, 'tile_type' => 'terrain_empty', 'is_explored' => 1, 'is_colony_zone' => 1, 'is_deep_scanned' => 0]
        );
    }

    private function setCc(array $attrs): void
    {
        DB::table('colony_buildings')->where('colony_id', self::COLONY_ID)->where('building_id', 25)->update($attrs);
    }

    private function place(int $buildingId, int $q, int $r)
    {
        return $this->actingAs($this->bart())
            ->postJson(route('colony.building.place'), ['building_id' => $buildingId, 'q' => $q, 'r' => $r]);
    }

    // ── Erect costs ──────────────────────────────────────────────────────────

    public function test_place_deducts_regolith(): void
    {
        config(['game.bypass.supply_checks' => true]);   // isolate the Regolith deduction
        $this->ensureBuildableTile(1, 0);
        $before = $this->colonyRes(self::RES_REGOLITH);

        $this->place(30, 1, 0)->assertOk()->assertJsonPath('ok', true);   // depot = 40 Rg

        $this->assertSame($before - 40, $this->colonyRes(self::RES_REGOLITH));
    }

    public function test_place_late_building_deducts_compounds(): void
    {
        config(['game.bypass.supply_checks' => true]);   // isolate the resource deduction
        $this->setCc(['level' => 2]);                    // sciencelab needs CC Lv2
        $this->ensureBuildableTile(1, 0);
        $rg = $this->colonyRes(self::RES_REGOLITH);
        $wk = $this->colonyRes(self::RES_COMPOUNDS);

        $this->place(31, 1, 0)->assertOk()->assertJsonPath('ok', true);   // sciencelab = 60 Rg + 20 Wk

        $this->assertSame($rg - 60, $this->colonyRes(self::RES_REGOLITH));
        $this->assertSame($wk - 20, $this->colonyRes(self::RES_COMPOUNDS));
    }

    public function test_place_rejected_without_enough_regolith(): void
    {
        $this->ensureBuildableTile(1, 0);
        $this->setColonyRes(self::RES_REGOLITH, 10);   // depot needs 40

        $this->place(30, 1, 0)->assertOk()->assertJsonPath('ok', false)->assertJsonPath('error', 'resource_limit');

        $this->assertSame(10, $this->colonyRes(self::RES_REGOLITH), 'no Regolith deducted on a rejected build');
        $this->assertFalse(
            DB::table('colony_buildings')->where('colony_id', self::COLONY_ID)->where('tile_x', 1)->where('tile_y', 0)->exists()
        );
    }

    public function test_supply_gate_blocks_when_cap_exhausted(): void
    {
        $this->ensureBuildableTile(1, 0);
        DB::table('user_resources')->where('user_id', self::BART_USER_ID)->update(['supply' => 0]);   // free cap ≤ 0

        $this->place(30, 1, 0)->assertOk()->assertJsonPath('ok', false)->assertJsonPath('error', 'supply_limit');
    }

    // ── Level-up costs ───────────────────────────────────────────────────────

    public function test_cc_levelup_deducts_scaled_regolith(): void
    {
        $this->setCc(['level' => 1, 'ap_spend' => 9, 'status_points' => 16]);   // 1 AP from Lv2 → 2×30 = 60 Rg
        $before = $this->colonyRes(self::RES_REGOLITH);

        $this->actingAs($this->bart())->postJson(route('colony.building.invest'), ['building_id' => 25])
            ->assertOk()->assertJsonPath('leveled_up', true);

        $this->assertSame($before - 60, $this->colonyRes(self::RES_REGOLITH));
    }

    public function test_levelup_blocked_without_regolith_keeps_ap(): void
    {
        $this->setCc(['level' => 1, 'ap_spend' => 9, 'status_points' => 16]);
        $this->setColonyRes(self::RES_REGOLITH, 10);   // < 60 needed for CC Lv2

        $this->actingAs($this->bart())->postJson(route('colony.building.invest'), ['building_id' => 25])
            ->assertOk()->assertJsonPath('ok', false)->assertJsonPath('error', 'resource_limit');

        $row = DB::table('colony_buildings')->where('colony_id', self::COLONY_ID)->where('building_id', 25)->first();
        $this->assertSame(9, (int) $row->ap_spend, 'AP must not be burned on a blocked level-up');
        $this->assertSame(1, (int) $row->level);
        $this->assertSame(10, $this->colonyRes(self::RES_REGOLITH));
    }

    // ── Repair costs ─────────────────────────────────────────────────────────

    public function test_repair_deducts_two_regolith(): void
    {
        $this->ensureBuildableTile(1, 0);
        config(['game.bypass.supply_checks' => true]);
        $this->place(30, 1, 0)->assertJsonPath('ok', true);
        DB::table('colony_buildings')->where('colony_id', self::COLONY_ID)->where('building_id', 30)
            ->update(['level' => 1, 'status_points' => 10]);
        $before = $this->colonyRes(self::RES_REGOLITH);

        $this->actingAs($this->bart())->postJson(route('colony.building.repair'), ['building_id' => 30])
            ->assertOk()->assertJsonPath('ok', true);

        $this->assertSame($before - 2, $this->colonyRes(self::RES_REGOLITH));
    }

    public function test_repair_hard_gate_without_regolith(): void
    {
        $this->ensureBuildableTile(1, 0);
        config(['game.bypass.supply_checks' => true]);
        $this->place(30, 1, 0)->assertJsonPath('ok', true);
        DB::table('colony_buildings')->where('colony_id', self::COLONY_ID)->where('building_id', 30)
            ->update(['level' => 1, 'status_points' => 10]);
        $this->setColonyRes(self::RES_REGOLITH, 0);

        $this->actingAs($this->bart())->postJson(route('colony.building.repair'), ['building_id' => 30])
            ->assertOk()->assertJsonPath('ok', false)->assertJsonPath('error', 'repair_no_regolith');

        $sp = (int) DB::table('colony_buildings')->where('colony_id', self::COLONY_ID)->where('building_id', 30)->value('status_points');
        $this->assertSame(10, $sp, 'no repair applied when Regolith is missing');
    }

    public function test_repair_cc_is_regolith_exempt(): void
    {
        $this->setCc(['level' => 2, 'status_points' => 10]);
        $this->setColonyRes(self::RES_REGOLITH, 0);

        $this->actingAs($this->bart())->postJson(route('colony.building.repair'), ['building_id' => 25])
            ->assertOk()->assertJsonPath('ok', true);

        $this->assertSame(0, $this->colonyRes(self::RES_REGOLITH), 'CC repair must not require Regolith');
        $sp = (int) DB::table('colony_buildings')->where('colony_id', self::COLONY_ID)->where('building_id', 25)->value('status_points');
        $this->assertSame(11, $sp);
    }

    // ── Nexus compound import ──────────────────────────────────────────────────

    public function test_nexus_import_requires_uplink(): void
    {
        DB::table('colony_buildings')->where('colony_id', self::COLONY_ID)->where('building_id', 54)->delete();

        $this->actingAs($this->bart())->postJson(route('colony.nexus.import'), ['amount' => 5])
            ->assertOk()->assertJsonPath('ok', false)->assertJsonPath('error', 'uplink_required');
    }

    public function test_nexus_import_spends_credits_and_grants_compounds(): void
    {
        DB::table('colony_buildings')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'building_id' => 54, 'instance_id' => 1],
            ['level' => 1, 'status_points' => 20, 'ap_spend' => 0, 'tile_x' => 2, 'tile_y' => 0]
        );
        $credits = $this->credits();
        $compounds = $this->colonyRes(self::RES_COMPOUNDS);
        $price = (int) config('game.economy.compound_import_price', 90);

        $this->actingAs($this->bart())->postJson(route('colony.nexus.import'), ['amount' => 10])
            ->assertOk()->assertJsonPath('ok', true)->assertJsonPath('amount', 10);

        $this->assertSame($credits - 10 * $price, $this->credits());
        $this->assertSame($compounds + 10, $this->colonyRes(self::RES_COMPOUNDS));
    }
}
