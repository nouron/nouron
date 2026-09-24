<?php

namespace Tests\Feature\Techtree;

use App\Models\User;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Tests\Concerns\CreatesForeignColony;
use Tests\TestCase;

/**
 * Techtree building orders (add / levelup / leveldown) act on exactly one
 * colony_buildings row — (colony_id, building_id, instance_id) — never on every
 * instance of an instanced building. A level-down (Rückbau) costs no resources and
 * has no levelup prerequisites; a level-up needs a placed tile (the anchored
 * Command Center excepted).
 *
 * Fixture colony 1 (Bart): CC 25 Lv3, harvester 27 Lv1 (one instance), housing 28
 * instances 1 (Lv2, ap_spend 2), 4 (Lv3), 5 (Lv2), sciencelab 31 Lv1, bar 52 Lv0 —
 * none of them with a tile.
 */
class TechtreeBuildingOrderTest extends TestCase
{
    use CreatesForeignColony;
    use RefreshDatabase;

    private const USER_ID = 3;

    private const COLONY_ID = 1;

    private const CC = 25;

    private const HARVESTER = 27;

    private const HOUSING = 28;

    private const SCIENCELAB = 31;

    private const BAR = 52;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
    }

    private function order(int $buildingId, array $payload): TestResponse
    {
        return $this->actingAs(User::find(self::USER_ID))
            ->postJson(route('techtree.order', ['type' => 'building', 'id' => $buildingId]), $payload);
    }

    private function place(int $buildingId, int $instanceId, int $q, int $r): void
    {
        DB::table('colony_buildings')
            ->where('colony_id', self::COLONY_ID)
            ->where('building_id', $buildingId)
            ->where('instance_id', $instanceId)
            ->update(['tile_x' => $q, 'tile_y' => $r]);
    }

    private function buildingRow(int $buildingId, int $instanceId, int $colonyId = self::COLONY_ID): object
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

    // ── Fehler 1: exactly one instance ────────────────────────────────────────

    public function test_leveldown_of_one_housing_instance_changes_only_that_instance(): void
    {
        $before = $this->housingSnapshot();

        $this->order(self::HOUSING, ['order' => 'leveldown', 'instance_id' => 4])
            ->assertOk()
            ->assertJsonPath('success', true);

        $after = $this->housingSnapshot();
        $this->assertSame(2, (int) $after[4]['level']);
        $this->assertEquals($before[1], $after[1], 'instance 1 must be untouched');
        $this->assertEquals($before[5], $after[5], 'instance 5 must be untouched');
    }

    public function test_leveldown_without_instance_id_on_multi_instance_building_is_rejected(): void
    {
        $before = $this->housingSnapshot();

        $this->order(self::HOUSING, ['order' => 'leveldown'])
            ->assertStatus(422)
            ->assertJsonPath('error', 'instance_required');

        $this->assertEquals($before, $this->housingSnapshot());
    }

    public function test_invest_without_instance_id_on_multi_instance_building_is_rejected(): void
    {
        $before = $this->housingSnapshot();

        $this->order(self::HOUSING, ['order' => 'add', 'ap' => 1])
            ->assertStatus(422)
            ->assertJsonPath('error', 'instance_required');

        $this->assertEquals($before, $this->housingSnapshot());
    }

    public function test_unknown_instance_id_is_rejected(): void
    {
        $before = $this->housingSnapshot();

        $this->order(self::HOUSING, ['order' => 'leveldown', 'instance_id' => 99])
            ->assertStatus(422)
            ->assertJsonPath('error', 'instance_not_found');

        $this->assertEquals($before, $this->housingSnapshot());
    }

    public function test_non_integer_instance_id_is_rejected(): void
    {
        $this->order(self::HOUSING, ['order' => 'leveldown', 'instance_id' => 'abc'])
            ->assertStatus(422)
            ->assertJsonPath('error', 'instance_not_found');
    }

    public function test_foreign_instance_id_is_not_reachable(): void
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
        $before = $this->housingSnapshot();

        $this->order(self::HOUSING, ['order' => 'leveldown', 'instance_id' => 7])
            ->assertStatus(422)
            ->assertJsonPath('error', 'instance_not_found');

        $this->assertSame(2, (int) $this->buildingRow(self::HOUSING, 7, $foreignColonyId)->level);
        $this->assertEquals($before, $this->housingSnapshot());
    }

    public function test_single_instance_building_is_chosen_implicitly(): void
    {
        $this->place(self::HARVESTER, 1, 1, 0);

        $this->order(self::HARVESTER, ['order' => 'leveldown'])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSame(0, (int) $this->buildingRow(self::HARVESTER, 1)->level);
    }

    public function test_invest_with_instance_id_touches_only_that_instance(): void
    {
        $this->place(self::HOUSING, 4, 0, 1);
        $before = $this->housingSnapshot();

        $this->order(self::HOUSING, ['order' => 'add', 'ap' => 1, 'instance_id' => 4])
            ->assertOk()
            ->assertJsonPath('success', true);

        $after = $this->housingSnapshot();
        $this->assertSame(1, (int) $after[4]['ap_spend']);
        $this->assertEquals($before[1], $after[1], 'instance 1 must be untouched');
        $this->assertEquals($before[5], $after[5], 'instance 5 must be untouched');
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

        $this->order(self::HOUSING, ['order' => 'leveldown', 'instance_id' => 5])->assertOk();

        $released = $this->buildingRow(self::HOUSING, 5);
        $this->assertSame(0, (int) $released->level);
        $this->assertNull($released->tile_x);
        $this->assertNull($released->tile_y);
        $this->assertSame(0, (int) $this->buildingRow(self::HOUSING, 1)->tile_x);
        $this->assertSame(1, (int) $this->buildingRow(self::HOUSING, 4)->tile_x);
    }

    // ── Fehler 2: Rückbau without costs, prerequisites or AP threshold ────────

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

        $this->order(self::SCIENCELAB, ['order' => 'leveldown'])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSame(0, (int) $this->buildingRow(self::SCIENCELAB, 1)->level);
        $this->assertEquals($userBefore, (array) DB::table('user_resources')->where('user_id', self::USER_ID)->first());
        $this->assertEquals($colonyBefore, DB::table('colony_resources')->where('colony_id', self::COLONY_ID)->orderBy('resource_id')->get()->toArray());
    }

    public function test_leveldown_ignores_levelup_prerequisites(): void
    {
        // The CC requires an Agrardom (41) for its levelup; colony 1 has none.
        $this->assertNull(DB::table('colony_buildings')->where('colony_id', self::COLONY_ID)->where('building_id', 41)->first());

        $this->order(self::CC, ['order' => 'leveldown'])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSame(2, (int) $this->buildingRow(self::CC, 1)->level);
    }

    public function test_command_center_leveldown_to_zero_names_the_min_level(): void
    {
        DB::table('colony_buildings')->where('colony_id', self::COLONY_ID)->where('building_id', self::CC)->update(['level' => 1]);

        $this->order(self::CC, ['order' => 'leveldown'])
            ->assertStatus(422)
            ->assertJsonPath('error', 'min_level');

        $this->assertSame(1, (int) $this->buildingRow(self::CC, 1)->level);
    }

    // ── Fehler 3: levelup/invest needs a placed tile ──────────────────────────

    public function test_levelup_of_unplaced_building_is_rejected_as_not_placed(): void
    {
        DB::table('colony_buildings')
            ->where('colony_id', self::COLONY_ID)
            ->where('building_id', self::BAR)
            ->update(['ap_spend' => 10]);

        $this->order(self::BAR, ['order' => 'levelup'])
            ->assertStatus(422)
            ->assertJsonPath('error', 'not_placed');

        $this->assertSame(0, (int) $this->buildingRow(self::BAR, 1)->level);
    }

    public function test_invest_into_unplaced_building_is_rejected_as_not_placed(): void
    {
        $this->order(self::BAR, ['order' => 'add', 'ap' => 10])
            ->assertStatus(422)
            ->assertJsonPath('error', 'not_placed');

        $bar = $this->buildingRow(self::BAR, 1);
        $this->assertSame(0, (int) $bar->level);
        $this->assertSame(0, (int) $bar->ap_spend);
    }

    public function test_placed_building_can_still_be_levelled_up(): void
    {
        $this->place(self::BAR, 1, 2, -1);

        $this->order(self::BAR, ['order' => 'add', 'ap' => 10])
            ->assertOk()
            ->assertJsonPath('leveled_up', true)
            ->assertJsonPath('tech.level', 1);

        $this->assertSame(1, (int) $this->buildingRow(self::BAR, 1)->level);
    }

    public function test_anchored_command_center_can_be_invested_without_a_tile(): void
    {
        DB::table('colony_buildings')->insert([
            'colony_id' => self::COLONY_ID,
            'building_id' => 41,
            'level' => 1,
            'status_points' => 20,
            'ap_spend' => 0,
            'tile_x' => 3,
            'tile_y' => -2,
        ]);
        $this->assertNull($this->buildingRow(self::CC, 1)->tile_x, 'precondition: the CC is anchored, not placed');

        $this->order(self::CC, ['order' => 'add', 'ap' => 1])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSame(1, (int) $this->buildingRow(self::CC, 1)->ap_spend);
    }

    public function test_new_order_codes_have_translations(): void
    {
        foreach (['de', 'en'] as $locale) {
            foreach (['instance_required', 'instance_not_found', 'not_placed', 'min_level'] as $code) {
                $key = "techtree.error_{$code}";
                $this->assertNotSame($key, __($key, [], $locale), "Missing {$locale} translation for {$key}");
            }
        }
    }
}
