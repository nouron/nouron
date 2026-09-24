<?php

namespace Tests\Feature\Techtree;

use App\Services\Techtree\BuildingService;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\CreatesForeignColony;
use Tests\TestCase;

/**
 * BuildingService operations (invest / levelup / leveldown) act on exactly one
 * colony_buildings row — (colony_id, building_id, instance_id) — never on every
 * instance of an instanced building. A level-down (Rückbau) costs no resources and
 * has no levelup prerequisites; a level-up needs a placed tile (the anchored
 * Command Center excepted).
 *
 * Formerly covered through the techtree order endpoint, which no longer accepts
 * building orders (A43) — building actions live in the colony view.
 *
 * Fixture colony 1 (Bart): CC 25 Lv3, harvester 27 Lv1 (one instance), housing 28
 * instances 1 (Lv2, ap_spend 2), 4 (Lv3), 5 (Lv2), sciencelab 31 Lv1, bar 52 Lv0 —
 * none of them with a tile.
 */
class BuildingServiceInstanceOrderTest extends TestCase
{
    use CreatesForeignColony;
    use RefreshDatabase;

    private const USER_ID = 3;

    private const COLONY_ID = 1;

    private const CC = 25;

    private const HOUSING = 28;

    private const SCIENCELAB = 31;

    private const BAR = 52;

    private BuildingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
        $this->service = $this->app->make(BuildingService::class);
    }

    private function place(int $buildingId, int $instanceId, int $q, int $r): void
    {
        DB::table('colony_buildings')
            ->where('colony_id', self::COLONY_ID)
            ->where('building_id', $buildingId)
            ->where('instance_id', $instanceId)
            ->update(['tile_x' => $q, 'tile_y' => $r]);
    }

    private function buildingRow(int $buildingId, int $instanceId, int $colonyId = self::COLONY_ID): ?object
    {
        return DB::table('colony_buildings')
            ->where('colony_id', $colonyId)
            ->where('building_id', $buildingId)
            ->where('instance_id', $instanceId)
            ->first();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function housingSnapshot(): array
    {
        return DB::table('colony_buildings')
            ->where('colony_id', self::COLONY_ID)
            ->where('building_id', self::HOUSING)
            ->orderBy('instance_id')
            ->get()
            ->mapWithKeys(fn ($row) => [(int) $row->instance_id => (array) $row])
            ->all();
    }

    // ── Exactly one instance ──────────────────────────────────────────────────

    public function test_leveldown_of_one_housing_instance_changes_only_that_instance(): void
    {
        $before = $this->housingSnapshot();

        $this->assertTrue($this->service->leveldown(self::COLONY_ID, self::HOUSING, 4));

        $after = $this->housingSnapshot();
        $this->assertSame(2, (int) $after[4]['level']);
        $this->assertEquals($before[1], $after[1], 'instance 1 must be untouched');
        $this->assertEquals($before[5], $after[5], 'instance 5 must be untouched');
    }

    public function test_invest_with_instance_id_touches_only_that_instance(): void
    {
        config(['game.bypass.ap_checks' => true]);
        $this->place(self::HOUSING, 4, 0, 1);
        $before = $this->housingSnapshot();

        $this->assertTrue($this->service->invest(self::COLONY_ID, self::HOUSING, 'add', 1, 4));

        $after = $this->housingSnapshot();
        $this->assertSame(1, (int) $after[4]['ap_spend']);
        $this->assertEquals($before[1], $after[1], 'instance 1 must be untouched');
        $this->assertEquals($before[5], $after[5], 'instance 5 must be untouched');
    }

    public function test_instance_blocker_requires_an_instance_on_multi_instance_buildings(): void
    {
        $this->assertSame('instance_required', $this->service->instanceBlocker(self::COLONY_ID, self::HOUSING, null));
        $this->assertNull($this->service->instanceBlocker(self::COLONY_ID, self::SCIENCELAB, null));
        $this->assertNull($this->service->instanceBlocker(self::COLONY_ID, self::HOUSING, 4));
    }

    public function test_instance_blocker_rejects_unknown_and_foreign_instances(): void
    {
        $foreignColonyId = $this->createForeignColony([25 => 1])['colony_id'];
        DB::table('colony_buildings')->insert([
            'colony_id' => $foreignColonyId,
            'building_id' => self::HOUSING,
            'instance_id' => 7,
            'level' => 2,
            'status_points' => 20,
            'ap_spend' => 0,
            'tile_x' => 0,
            'tile_y' => 1,
        ]);

        $this->assertSame('instance_not_found', $this->service->instanceBlocker(self::COLONY_ID, self::HOUSING, 99));
        $this->assertSame('instance_not_found', $this->service->instanceBlocker(self::COLONY_ID, self::HOUSING, 7));
    }

    public function test_leveldown_of_a_missing_instance_changes_nothing(): void
    {
        $before = $this->housingSnapshot();

        $this->assertFalse($this->service->leveldown(self::COLONY_ID, self::HOUSING, 99));

        $this->assertEquals($before, $this->housingSnapshot());
        $this->assertNull($this->buildingRow(self::HOUSING, 99));
    }

    public function test_leveldown_to_zero_releases_only_that_instances_tile(): void
    {
        $this->place(self::HOUSING, 1, 0, 1);
        $this->place(self::HOUSING, 4, 1, -1);
        $this->place(self::HOUSING, 5, -1, 1);
        DB::table('colony_buildings')
            ->where('colony_id', self::COLONY_ID)
            ->where('building_id', self::HOUSING)
            ->where('instance_id', 5)
            ->update(['level' => 1]);

        $this->assertTrue($this->service->leveldown(self::COLONY_ID, self::HOUSING, 5));

        $released = $this->buildingRow(self::HOUSING, 5);
        $this->assertSame(0, (int) $released->level);
        $this->assertNull($released->tile_x);
        $this->assertNull($released->tile_y);
        $this->assertSame(0, (int) $this->buildingRow(self::HOUSING, 1)->tile_x);
        $this->assertSame(1, (int) $this->buildingRow(self::HOUSING, 4)->tile_x);
    }

    public function test_placed_instances_lists_tiled_rows_and_the_anchored_command_center(): void
    {
        $this->place(self::HOUSING, 5, -1, 1);
        $this->place(self::HOUSING, 1, 0, 1);

        $instances = $this->service->placedInstances(self::COLONY_ID);

        $this->assertSame([1, 5], array_column($instances[self::HOUSING], 'instance_id'));
        $this->assertSame(['q' => -1, 'r' => 1], array_intersect_key($instances[self::HOUSING][1], ['q' => 0, 'r' => 0]));
        $this->assertCount(1, $instances[self::CC], 'the anchored Command Center counts as placed');
        $this->assertSame(3, $instances[self::CC][0]['level']);
        $this->assertNull($instances[self::CC][0]['q']);
        $this->assertNull($instances[self::CC][0]['r']);
        $this->assertArrayNotHasKey(self::BAR, $instances, 'a row without a tile is not placed');
    }

    // ── Rückbau without costs, prerequisites or AP threshold ──────────────────

    public function test_leveldown_charges_no_resources_and_needs_no_invested_ap(): void
    {
        config([
            'game.bypass.resource_costs' => false,
            'game.bypass.supply_checks' => false,
            'game.bypass.ap_checks' => false,
        ]);
        // sciencelab costs credits (1) + supply (2) per fixture; ap_spend 0 < ap_for_levelup.
        $this->place(self::SCIENCELAB, 1, 2, -1);
        $userBefore = (array) DB::table('user_resources')->where('user_id', self::USER_ID)->first();
        $colonyBefore = DB::table('colony_resources')->where('colony_id', self::COLONY_ID)->orderBy('resource_id')->get()->toArray();

        $this->assertTrue($this->service->leveldown(self::COLONY_ID, self::SCIENCELAB, 1));

        $this->assertSame(0, (int) $this->buildingRow(self::SCIENCELAB, 1)->level);
        $this->assertEquals($userBefore, (array) DB::table('user_resources')->where('user_id', self::USER_ID)->first());
        $this->assertEquals($colonyBefore, DB::table('colony_resources')->where('colony_id', self::COLONY_ID)->orderBy('resource_id')->get()->toArray());
    }

    public function test_leveldown_ignores_levelup_prerequisites(): void
    {
        // The CC requires an Agrardom (41) for its levelup; colony 1 has none.
        $this->assertNull(DB::table('colony_buildings')->where('colony_id', self::COLONY_ID)->where('building_id', 41)->first());

        $this->assertNull($this->service->leveldownBlocker(self::COLONY_ID, self::CC, 1));
        $this->assertTrue($this->service->leveldown(self::COLONY_ID, self::CC, 1));

        $this->assertSame(2, (int) $this->buildingRow(self::CC, 1)->level);
    }

    public function test_command_center_leveldown_to_zero_names_the_min_level(): void
    {
        DB::table('colony_buildings')->where('colony_id', self::COLONY_ID)->where('building_id', self::CC)->update(['level' => 1]);

        $this->assertSame('min_level', $this->service->leveldownBlocker(self::COLONY_ID, self::CC, 1));
        $this->assertFalse($this->service->leveldown(self::COLONY_ID, self::CC, 1));

        $this->assertSame(1, (int) $this->buildingRow(self::CC, 1)->level);
    }

    // ── levelup/invest needs a placed tile ────────────────────────────────────

    public function test_levelup_of_unplaced_building_is_rejected_as_not_placed(): void
    {
        DB::table('colony_buildings')
            ->where('colony_id', self::COLONY_ID)
            ->where('building_id', self::BAR)
            ->update(['ap_spend' => 10]);

        $this->assertSame('not_placed', $this->service->levelupBlocker(self::COLONY_ID, self::BAR, 1));
        $this->assertFalse($this->service->levelup(self::COLONY_ID, self::BAR, 1));

        $this->assertSame(0, (int) $this->buildingRow(self::BAR, 1)->level);
    }

    public function test_invest_into_unplaced_building_is_rejected_as_not_placed(): void
    {
        config(['game.bypass.ap_checks' => true]);

        $this->assertSame('not_placed', $this->service->investBlocker(self::COLONY_ID, self::BAR, 'add', 10, instanceId: 1));
        $this->assertFalse($this->service->invest(self::COLONY_ID, self::BAR, 'add', 10, 1));

        $bar = $this->buildingRow(self::BAR, 1);
        $this->assertSame(0, (int) $bar->level);
        $this->assertSame(0, (int) $bar->ap_spend);
    }

    public function test_placed_building_can_still_be_levelled_up(): void
    {
        config(['game.bypass.ap_checks' => true, 'game.bypass.resource_costs' => true]);
        $this->place(self::BAR, 1, 2, -1);

        $this->assertTrue($this->service->invest(self::COLONY_ID, self::BAR, 'add', 10, 1));
        $this->assertTrue($this->service->levelup(self::COLONY_ID, self::BAR, 1));

        $this->assertSame(1, (int) $this->buildingRow(self::BAR, 1)->level);
    }

    public function test_anchored_command_center_can_be_invested_without_a_tile(): void
    {
        config(['game.bypass.ap_checks' => true]);
        $this->assertNull($this->buildingRow(self::CC, 1)->tile_x, 'precondition: the CC is anchored, not placed');

        $this->assertTrue($this->service->invest(self::COLONY_ID, self::CC, 'add', 1, 1));

        $this->assertSame(1, (int) $this->buildingRow(self::CC, 1)->ap_spend);
    }
}
