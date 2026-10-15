<?php

namespace Tests\Feature\Colony;

use App\Models\ColonyTile;
use App\Models\User;
use App\Services\AdvisorService;
use App\Services\ColonyTileService;
use App\Services\TickService;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/** Self-salvage (no hangar): one instant action, flat AP per find size (Owner 2026-10-13). */
class SalvageFindTest extends TestCase
{
    use RefreshDatabase;

    private const COLONY_ID = 1;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
        DB::table('colony_buildings')->where('colony_id', self::COLONY_ID)->where('building_id', 44)->delete(); // Pool v1 self-salvage = colony without hangar (T30 §13.4)
        config(['game.bypass.ap_checks' => false]);
        $this->atTick(10);
    }

    /** Rebind the request-scoped clock and drop already-resolved services holding the old one. */
    private function atTick(int $tick): void
    {
        $this->app->instance(TickService::class, new TickService($tick));
        $this->app->forgetInstance(AdvisorService::class);
        $this->app->forgetInstance(ColonyTileService::class);
    }

    private function tile(int $q, ?string $eventType, array $extra = []): void
    {
        DB::table('colony_tiles')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'q' => $q, 'r' => 0],
            array_merge(['ring' => 2, 'tile_type' => 'terrain_empty', 'is_explored' => 1,
                'is_colony_zone' => 0, 'is_deep_scanned' => 1, 'event_type' => $eventType,
            ], $extra)
        );
    }

    private function row(int $q): object
    {
        return DB::table('colony_tiles')->where('colony_id', self::COLONY_ID)->where('q', $q)->where('r', 0)->first();
    }

    private function navAp(): int
    {
        return $this->app->make(AdvisorService::class)->getAvailableActionPoints(self::COLONY_ID);
    }

    private function regolith(): int
    {
        return (int) DB::table('colony_resources')->where('colony_id', self::COLONY_ID)->where('resource_id', 3)->value('amount');
    }

    private function salvage(int $q): array
    {
        return $this->app->make(ColonyTileService::class)->salvageFind(self::COLONY_ID, $q, 0);
    }

    public function test_salvage_is_instant_costs_flat_ap_and_credits_regolith(): void
    {
        foreach (['find_small' => [3, 4], 'find_medium' => [4, 10], 'find_large' => [6, 18]] as $type => [$apCost, $rgGain]) {
            $this->tile(2, $type);
            $ap = $this->navAp();
            $rg = $this->regolith();

            $res = $this->salvage(2);

            $this->assertTrue($res['ok'], $type);
            $this->assertTrue($res['completed']);
            $this->assertSame($rgGain, $res['regolith']);
            $this->assertSame($ap - $apCost, $this->navAp(), $type);
            $this->assertSame($rg + $rgGain, $this->regolith(), $type);
            $this->assertNull($this->row(2)->event_type);
        }
    }

    public function test_no_per_sol_cap_and_no_project_limit(): void
    {
        foreach ([2, 3, 4] as $q) {
            $this->tile($q, 'find_small');
        }

        foreach ([2, 3, 4] as $q) {
            $this->assertTrue($this->salvage($q)['ok'], "tile $q");
        }
    }

    public function test_second_salvage_of_same_tile_rejected_without_charge(): void
    {
        $this->tile(2, 'find_small');
        $this->assertTrue($this->salvage(2)['ok']);
        $ap = $this->navAp();
        $rg = $this->regolith();

        $res = $this->salvage(2);

        $this->assertFalse($res['ok']);
        $this->assertSame($ap, $this->navAp());
        $this->assertSame($rg, $this->regolith());
    }

    public function test_wrong_tile_states(): void
    {
        $this->tile(2, 'find_false');
        $this->tile(3, 'find_small', ['is_deep_scanned' => 0]);
        $this->tile(4, null);

        $this->assertSame('find_false', $this->salvage(2)['error']);
        $this->assertSame('not_scanned', $this->salvage(3)['error']);
        $this->assertSame('no_find', $this->salvage(4)['error']);
        $this->assertSame('tile_not_found', $this->salvage(9)['error']);
    }

    public function test_not_enough_ap(): void
    {
        $this->tile(2, 'find_small');
        $this->app->make(AdvisorService::class)->lockActionPoints(self::COLONY_ID, $this->navAp());

        $res = $this->salvage(2);

        $this->assertSame('no_nav_ap', $res['error']);
        $this->assertSame('find_small', $this->row(2)->event_type);
    }

    public function test_placing_building_on_find_tile_rejected(): void
    {
        $user = User::where('user_id', 3)->firstOrFail();
        $this->tile(2, 'find_small', ['is_colony_zone' => 1, 'is_deep_scanned' => 0]);

        $res = $this->actingAs($user)->postJson(route('colony.building.place'), ['building_id' => 28, 'q' => 2, 'r' => 0]);

        $res->assertStatus(422)->assertJson(['error' => 'tile_has_find']);
    }

    public function test_stale_read_cannot_double_credit_or_double_pay(): void
    {
        $this->tile(2, 'find_small');
        $stale = ColonyTile::where('colony_id', self::COLONY_ID)->where('q', 2)->first();
        $rg = $this->regolith();
        $this->assertTrue($this->salvage(2)['completed']);
        $ap = $this->navAp();

        $res = $this->salvage(2);

        $this->assertFalse($res['ok']);
        $this->assertSame($rg + 4, $this->regolith());
        $this->assertSame($ap, $this->navAp());
        $this->assertNotNull($stale);
    }

    private function place(int $q): TestResponse
    {
        $user = User::where('user_id', 3)->firstOrFail();

        return $this->actingAs($user)->postJson(route('colony.building.place'), ['building_id' => 28, 'q' => $q, 'r' => 0]);
    }

    public function test_placement_guard_matrix(): void
    {
        $z = ['is_colony_zone' => 1];
        $this->tile(2, 'find_false', $z + ['is_deep_scanned' => 0]);
        $this->tile(3, 'find_false', $z + ['is_deep_scanned' => 1]);
        $this->tile(4, 'find_small', $z + ['is_deep_scanned' => 1]);
        $this->tile(5, 'event_ruin', $z + ['is_deep_scanned' => 0]);
        $this->tile(6, null, $z + ['is_deep_scanned' => 0]);

        $this->place(2)->assertStatus(422)->assertJson(['error' => 'tile_has_find']);
        $this->place(4)->assertStatus(422)->assertJson(['error' => 'tile_has_find']);
        $this->assertNotSame('tile_has_find', $this->place(3)->json('error'));
        $this->assertNotSame('tile_has_find', $this->place(5)->json('error'));
        $this->assertNotSame('tile_has_find', $this->place(6)->json('error'));
    }

    public function test_salvage_route_returns_ap_and_regolith(): void
    {
        $user = User::where('user_id', 3)->firstOrFail();
        $this->tile(2, 'find_small');

        $res = $this->actingAs($user)->postJson(route('colony.tile.salvage'), ['q' => 2, 'r' => 0]);

        $res->assertOk()->assertJsonStructure(['ok', 'completed', 'apAvailable', 'regolith']);
    }
}
