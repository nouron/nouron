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

/** T30 Pool v1: salvage project (multi-Sol AP deposits, per-Sol cap, open-project limit). */
class SalvageFindTest extends TestCase
{
    use RefreshDatabase;

    private const COLONY_ID = 1;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
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
                'salvage_ap_spent' => 0, 'salvage_tick' => null], $extra)
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

    private function salvage(int $q, int $ap): array
    {
        return $this->app->make(ColonyTileService::class)->salvageFind(self::COLONY_ID, $q, 0, $ap);
    }

    public function test_deposit_locks_ap_and_does_not_complete(): void
    {
        $this->tile(2, 'find_small');
        $ap = $this->navAp();
        $rg = $this->regolith();

        $res = $this->salvage(2, 4);

        $this->assertTrue($res['ok']);
        $this->assertFalse($res['completed']);
        $this->assertSame(4, $this->row(2)->salvage_ap_spent);
        $this->assertSame($ap - 4, $this->navAp());
        $this->assertSame($rg, $this->regolith());
    }

    public function test_three_sols_complete_and_credit_regolith_once(): void
    {
        $this->tile(2, 'find_small');
        $rg = $this->regolith();

        $this->assertFalse($this->salvage(2, 4)['completed']);
        $this->atTick(11);
        $this->assertFalse($this->salvage(2, 4)['completed']);
        $this->atTick(12);
        $res = $this->salvage(2, 4);

        $this->assertTrue($res['ok']);
        $this->assertTrue($res['completed']);
        $this->assertSame(4, $res['regolith']);
        $this->assertSame($rg + 4, $this->regolith());
        $row = $this->row(2);
        $this->assertNull($row->event_type);
        $this->assertSame(0, (int) $row->salvage_ap_spent);
        $this->assertSame('not_scanned', $this->salvage(2, 1)['error']);
        $this->assertSame($rg + 4, $this->regolith());
    }

    public function test_last_deposit_is_trimmed_to_remainder(): void
    {
        $this->tile(2, 'find_small', ['salvage_ap_spent' => 10, 'salvage_tick' => 9]);
        $ap = $this->navAp();

        $res = $this->salvage(2, 4);

        $this->assertTrue($res['completed']);
        $this->assertSame($ap - 2, $this->navAp());
    }

    public function test_second_deposit_same_sol_rejected(): void
    {
        $this->tile(2, 'find_medium');
        $this->assertTrue($this->salvage(2, 2)['ok']);
        $ap = $this->navAp();

        $res = $this->salvage(2, 1);

        $this->assertSame('salvage_cap', $res['error']);
        $this->assertSame($ap, $this->navAp());
        $this->assertSame(2, $this->row(2)->salvage_ap_spent);
    }

    public function test_invalid_ap_rejected(): void
    {
        $this->tile(2, 'find_small');

        $this->assertSame('invalid_ap', $this->salvage(2, 5)['error']);
        $this->assertSame('invalid_ap', $this->salvage(2, 0)['error']);
        $this->assertSame(0, (int) $this->row(2)->salvage_ap_spent);
    }

    public function test_third_project_rejected_but_open_one_can_finish(): void
    {
        $this->tile(2, 'find_small', ['salvage_ap_spent' => 4, 'salvage_tick' => 9]);
        $this->tile(3, 'find_small', ['salvage_ap_spent' => 10, 'salvage_tick' => 9]);
        $this->tile(4, 'find_small');

        $res = $this->salvage(4, 2);
        $this->assertSame('salvage_projects', $res['error']);
        $this->assertSame(0, (int) $this->row(4)->salvage_ap_spent);

        $this->assertTrue($this->salvage(3, 2)['completed']);
        $this->assertTrue($this->salvage(4, 2)['ok']);
    }

    public function test_wrong_tile_states(): void
    {
        $this->tile(2, 'find_false');
        $this->tile(3, 'find_small', ['is_deep_scanned' => 0]);
        $this->tile(4, null);

        $this->assertSame('find_false', $this->salvage(2, 2)['error']);
        $this->assertSame('not_scanned', $this->salvage(3, 2)['error']);
        $this->assertSame('no_find', $this->salvage(4, 2)['error']);
        $this->assertSame('tile_not_found', $this->salvage(9, 2)['error']);
    }

    public function test_not_enough_ap(): void
    {
        $this->tile(2, 'find_small');
        $this->app->make(AdvisorService::class)->lockActionPoints(self::COLONY_ID, $this->navAp());

        $res = $this->salvage(2, 2);

        $this->assertSame('no_nav_ap', $res['error']);
        $this->assertSame(0, (int) $this->row(2)->salvage_ap_spent);
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
        $this->tile(2, 'find_small', ['salvage_ap_spent' => 10, 'salvage_tick' => 9]);
        $stale = ColonyTile::where('colony_id', self::COLONY_ID)->where('q', 2)->first();
        $rg = $this->regolith();
        $this->assertTrue($this->salvage(2, 4)['completed']);
        $ap = $this->navAp();

        $res = $this->salvage(2, 4);

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

        $res = $this->actingAs($user)->postJson(route('colony.tile.salvage'), ['q' => 2, 'r' => 0, 'ap' => 4]);

        $res->assertOk()->assertJsonStructure(['ok', 'completed', 'apAvailable', 'regolith']);
    }
}
