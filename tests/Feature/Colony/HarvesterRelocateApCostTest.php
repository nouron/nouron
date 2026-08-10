<?php

namespace Tests\Feature\Colony;

use App\Models\User;
use App\Services\TickService;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Harvester relocation AP cost (GDD §4c "Verlegekosten 1 → 2 AP je Hex",
 * freigegeben 2026-08-03) — the relocation-frequency lever, not the
 * depletion curve. A typical four-hex move now costs 8 Construction-AP
 * instead of 4.
 *
 * Fixture: Colony 1 (Springfield), user_id=3 (Bart), harvester building_id=27.
 */
class HarvesterRelocateApCostTest extends TestCase
{
    use RefreshDatabase;

    private const COLONY_ID = 1;

    private const BART_USER_ID = 3;

    private const HARVESTER_ID = 27;

    private const TRUST_RES_ID = 12;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();

        // Neutral trust → AP multiplier 1.0.
        DB::table('colony_resources')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'resource_id' => self::TRUST_RES_ID],
            ['amount' => 0]
        );
        DB::table('trust_events')->where('colony_id', self::COLONY_ID)->delete();

        // phpunit.xml bypasses AP checks by default (GAME_BYPASS_AP=true) — disable it
        // here so the real AP gate/lock path is exercised (see BuildingRepairTest).
        config(['game.bypass.ap_checks' => false]);

        // Ample Construction-AP so an 8-AP move is never blocked by the gate itself
        // (isolates the AP-cost assertion from the availability check).
        config(['game.ap.base' => 20]);

        // Harvester at level 1 on tile (0,0) — 4 hexes from the (4,0) target.
        DB::table('colony_buildings')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'building_id' => self::HARVESTER_ID, 'instance_id' => 1],
            ['level' => 1, 'status_points' => 16, 'ap_spend' => 0, 'tile_x' => 0, 'tile_y' => 0, 'pending_until_tick' => null]
        );

        DB::table('colony_tiles')->insertOrIgnore([
            ['colony_id' => self::COLONY_ID, 'q' => 0, 'r' => 0, 'ring' => 0, 'tile_type' => 'terrain_empty', 'is_explored' => 1, 'is_colony_zone' => 1, 'is_deep_scanned' => 0, 'resource_amount' => null, 'resource_max' => null],
            ['colony_id' => self::COLONY_ID, 'q' => 4, 'r' => 0, 'ring' => 4, 'tile_type' => 'regolith_normal', 'is_explored' => 1, 'is_colony_zone' => 0, 'is_deep_scanned' => 0, 'resource_amount' => 300, 'resource_max' => 300],
        ]);
    }

    private function makeUser(): User
    {
        return User::where('user_id', self::BART_USER_ID)->firstOrFail();
    }

    private function currentTick(): int
    {
        return $this->app->make(TickService::class)->getTickCount();
    }

    private function lockedConstructionAp(): int
    {
        $tick = $this->currentTick();

        return (int) DB::table('locked_actionpoints')
            ->where('tick', $tick)
            ->where('scope_type', 'colony')
            ->where('scope_id', self::COLONY_ID)
            ->where('personell_id', config('advisors.engineer.id', 35))
            ->value('spend_ap') ?? 0;
    }

    public function test_four_hex_move_costs_eight_ap(): void
    {
        $response = $this->actingAs($this->makeUser())
            ->postJson(route('colony.building.place'), [
                'building_id' => self::HARVESTER_ID,
                'q' => 4,
                'r' => 0,
            ]);

        $response->assertOk()->assertJsonPath('ok', true);
        $this->assertSame(8, $this->lockedConstructionAp(), 'A 4-hex relocation must cost 4 × 2 = 8 Construction-AP (GDD §4c)');
    }
}
