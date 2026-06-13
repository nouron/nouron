<?php

namespace Tests\Feature\Colony;

use App\Models\User;
use App\Services\TickService;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Harvester relocation transit — moving the harvester takes 1 Sol.
 *
 * Rules:
 * - A relocation sets pending_until_tick = currentTick + 1.
 * - While in transit (pending_until_tick >= tick) the harvester
 *   produces nothing and cannot be relocated again.
 * - The tick after arrival clears pending_until_tick and production resumes.
 *
 * Fixture: Colony 1 (Springfield), user_id=3 (Bart), harvester building_id=27.
 */
class HarvesterTransitTest extends TestCase
{
    use RefreshDatabase;

    private const COLONY_ID = 1;

    private const BART_USER_ID = 3;

    private const HARVESTER_ID = 27;

    private const RES_REGOLITH = 3;

    private const TRUST_RES_ID = 12;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();

        // Neutral trust → production multiplier 1.0, AP multiplier 1.0
        DB::table('colony_resources')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'resource_id' => self::TRUST_RES_ID],
            ['amount' => 0]
        );
        DB::table('trust_events')->where('colony_id', self::COLONY_ID)->delete();

        // Harvester at level 1 on colony-zone tile (1,0)
        DB::table('colony_buildings')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'building_id' => self::HARVESTER_ID, 'instance_id' => 1],
            ['level' => 1, 'status_points' => 16, 'ap_spend' => 0, 'tile_x' => 1, 'tile_y' => 0, 'pending_until_tick' => null]
        );

        // Tiles: start tile + two explored regolith targets outside the colony zone
        DB::table('colony_tiles')->insertOrIgnore([
            ['colony_id' => self::COLONY_ID, 'q' => 1, 'r' => 0, 'ring' => 1, 'tile_type' => 'terrain_empty', 'is_explored' => 1, 'is_colony_zone' => 1, 'is_deep_scanned' => 0],
            ['colony_id' => self::COLONY_ID, 'q' => 3, 'r' => 0, 'ring' => 3, 'tile_type' => 'regolith_normal', 'is_explored' => 1, 'is_colony_zone' => 0, 'is_deep_scanned' => 0],
            ['colony_id' => self::COLONY_ID, 'q' => -3, 'r' => 0, 'ring' => 3, 'tile_type' => 'regolith_poor', 'is_explored' => 1, 'is_colony_zone' => 0, 'is_deep_scanned' => 0],
        ]);
    }

    private function makeUser(int $userId): User
    {
        return User::where('user_id', $userId)->firstOrFail();
    }

    private function currentTick(): int
    {
        return $this->app->make(TickService::class)->getTickCount();
    }

    private function harvesterRow(): object
    {
        return DB::table('colony_buildings')
            ->where('colony_id', self::COLONY_ID)
            ->where('building_id', self::HARVESTER_ID)
            ->first();
    }

    private function moveHarvester(int $q, int $r)
    {
        return $this->actingAs($this->makeUser(self::BART_USER_ID))
            ->postJson(route('colony.building.place'), [
                'building_id' => self::HARVESTER_ID,
                'q' => $q,
                'r' => $r,
            ]);
    }

    private function regolithAmount(): int
    {
        return (int) DB::table('colony_resources')
            ->where('colony_id', self::COLONY_ID)
            ->where('resource_id', self::RES_REGOLITH)
            ->value('amount');
    }

    // ── Transit is set on relocation ─────────────────────────────────────────

    public function test_move_sets_pending_until_next_tick(): void
    {
        $tick = $this->currentTick();

        $response = $this->moveHarvester(3, 0);

        $response->assertOk()->assertJsonPath('ok', true);
        $row = $this->harvesterRow();
        $this->assertSame(3, (int) $row->tile_x);
        $this->assertSame($tick + 1, (int) $row->pending_until_tick);
        $this->assertTrue($response->json('building.in_transit'));
    }

    public function test_initial_placement_does_not_set_transit(): void
    {
        // Unplaced harvester (tile_x null) — first placement is not a relocation.
        DB::table('colony_buildings')
            ->where('colony_id', self::COLONY_ID)
            ->where('building_id', self::HARVESTER_ID)
            ->update(['tile_x' => null, 'tile_y' => null]);

        $this->moveHarvester(3, 0)->assertOk()->assertJsonPath('ok', true);

        $this->assertNull($this->harvesterRow()->pending_until_tick);
    }

    // ── Second move blocked while in transit ─────────────────────────────────

    public function test_second_move_blocked_while_in_transit(): void
    {
        $this->moveHarvester(3, 0)->assertJsonPath('ok', true);

        $response = $this->moveHarvester(-3, 0);

        $response->assertOk()->assertJsonPath('ok', false);
        $this->assertSame(__('colony.error_harvester_in_transit'), $response->json('error'));
        // Position unchanged
        $this->assertSame(3, (int) $this->harvesterRow()->tile_x);
    }

    public function test_move_allowed_again_after_arrival(): void
    {
        $this->moveHarvester(3, 0)->assertJsonPath('ok', true);

        // Simulate arrival: transit ended before the current tick.
        DB::table('colony_buildings')
            ->where('colony_id', self::COLONY_ID)
            ->where('building_id', self::HARVESTER_ID)
            ->update(['pending_until_tick' => $this->currentTick() - 1]);

        $this->moveHarvester(-3, 0)->assertJsonPath('ok', true);
        $this->assertSame(-3, (int) $this->harvesterRow()->tile_x);
    }

    // ── Production pauses during transit ─────────────────────────────────────

    public function test_no_production_while_in_transit(): void
    {
        $tick = 11300;
        DB::table('colony_buildings')
            ->where('colony_id', self::COLONY_ID)
            ->where('building_id', self::HARVESTER_ID)
            ->update(['pending_until_tick' => $tick]);

        $before = $this->regolithAmount();
        Artisan::call('game:tick', ['--tick' => $tick]);

        $this->assertSame($before, $this->regolithAmount(), 'Harvester in transit must not produce');
    }

    public function test_production_resumes_and_pending_cleared_after_arrival(): void
    {
        $tick = 11301;
        DB::table('colony_buildings')
            ->where('colony_id', self::COLONY_ID)
            ->where('building_id', self::HARVESTER_ID)
            ->update(['pending_until_tick' => $tick - 1]);

        $before = $this->regolithAmount();
        Artisan::call('game:tick', ['--tick' => $tick]);

        $this->assertGreaterThan($before, $this->regolithAmount(), 'Arrived harvester must produce again');
        $this->assertNull($this->harvesterRow()->pending_until_tick, 'Arrived transit must be cleared');
    }
}
