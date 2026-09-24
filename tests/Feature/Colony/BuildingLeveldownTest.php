<?php

namespace Tests\Feature\Colony;

use App\Models\User;
use App\Services\AdvisorService;
use App\Services\ResourcesService;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Tests\Concerns\CreatesForeignColony;
use Tests\TestCase;

/**
 * Rückbau in the colony view (A43): POST /colony/building/leveldown lowers exactly
 * one building instance by one level, free of charge. Level 1 → 0 takes the building
 * off its tile and drops the first-level workplace reserve (BuildingService::leveldown()).
 * The Command Center never goes below 1.
 *
 * Fixture colony 1 (user 3): CC 25 Lv3, housing 28 instances 1 (Lv2), 4 (Lv3),
 * 5 (Lv2), sciencelab 31 Lv1 — all without a tile; tests place what they need.
 */
class BuildingLeveldownTest extends TestCase
{
    use CreatesForeignColony;
    use RefreshDatabase;

    private const USER_ID = 3;

    private const COLONY_ID = 1;

    private const CC = 25;

    private const HOUSING = 28;

    private const SCIENCELAB = 31;

    private const HANGAR = 44;

    private const BAR = 52;

    private const HOUSING_CAP_PER_LEVEL = 8;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();

        config([
            'game.ap.base' => 30,
            'game.bypass.ap_checks' => false,
            'game.bypass.resource_costs' => false,
            'game.bypass.supply_checks' => false,
        ]);
        DB::table('buildings')->where('id', self::SCIENCELAB)->update(['supply_cost' => 6]);
    }

    private function leveldown(array $payload): TestResponse
    {
        return $this->actingAs(User::where('user_id', self::USER_ID)->firstOrFail())
            ->postJson(route('colony.building.leveldown'), $payload);
    }

    private function preview(array $query): TestResponse
    {
        return $this->actingAs(User::where('user_id', self::USER_ID)->firstOrFail())
            ->getJson(route('colony.building.leveldown-preview', $query));
    }

    /**
     * Stored cap = the cap a Sol calculates, filled exactly by a placed Lv1 Cantina
     * (its supply cost set to the free rest): nobody homeless, no free supply.
     */
    private function fillCapExactly(): void
    {
        $resources = $this->app->make(ResourcesService::class);
        DB::table('user_resources')->where('user_id', self::USER_ID)->update(['supply' => $resources->calculateSupplyCap(self::COLONY_ID)]);
        DB::table('glx_colonies')->where('id', self::COLONY_ID)->update(['overcap_departed' => 0, 'overcap_streak' => 0]);
        $free = $resources->getFreeSupply(self::COLONY_ID);
        $this->assertGreaterThanOrEqual(0, $free, 'precondition: fixture colony within its cap');
        DB::table('buildings')->where('id', self::BAR)->update(['supply_cost' => $free]);
        DB::table('colony_buildings')->where('colony_id', self::COLONY_ID)->where('building_id', self::BAR)->update(['level' => 1, 'tile_x' => -2, 'tile_y' => 1]);
        $this->assertSame(0, $resources->getFreeSupply(self::COLONY_ID));
    }

    private function place(int $buildingId, int $instanceId, int $q, int $r, ?int $level = null): void
    {
        DB::table('colony_buildings')
            ->where('colony_id', self::COLONY_ID)
            ->where('building_id', $buildingId)
            ->where('instance_id', $instanceId)
            ->update(['tile_x' => $q, 'tile_y' => $r] + ($level !== null ? ['level' => $level] : []));
    }

    private function row(int $buildingId, int $instanceId, int $colonyId = self::COLONY_ID): ?object
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

    public function test_leveldown_lowers_only_the_given_instance(): void
    {
        $this->place(self::HOUSING, 1, 0, 1);
        $this->place(self::HOUSING, 4, 1, -1);
        $this->place(self::HOUSING, 5, -1, 1);
        $before = $this->housingSnapshot();

        $this->leveldown(['building_id' => self::HOUSING, 'instance_id' => 4])
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('level', 2)
            ->assertJsonPath('tile_released', false)
            ->assertJsonPath('released_tile', null)
            ->assertJsonPath('building.building_id', self::HOUSING)
            ->assertJsonPath('building.instance_id', 4)
            ->assertJsonPath('building.level', 2)
            ->assertJsonPath('building.tile_x', 1)
            ->assertJsonPath('building.tile_y', -1);

        $after = $this->housingSnapshot();
        $this->assertSame(2, (int) $after[4]['level']);
        $this->assertEquals($before[1], $after[1], 'instance 1 must be untouched');
        $this->assertEquals($before[5], $after[5], 'instance 5 must be untouched');
    }

    public function test_leveldown_from_one_to_zero_releases_tile_and_reserve(): void
    {
        $this->place(self::SCIENCELAB, 1, 2, -1);
        $resources = $this->app->make(ResourcesService::class);
        $freeBefore = $resources->getFreeSupply(self::COLONY_ID);

        $response = $this->leveldown(['building_id' => self::SCIENCELAB, 'instance_id' => 1])
            ->assertOk()
            ->assertJsonPath('level', 0)
            ->assertJsonPath('tile_released', true)
            ->assertJsonPath('released_tile', ['q' => 2, 'r' => -1])
            ->assertJsonPath('building.tile_x', null);

        $row = $this->row(self::SCIENCELAB, 1);
        $this->assertSame(0, (int) $row->level);
        $this->assertNull($row->tile_x);
        $this->assertNull($row->tile_y);
        $this->assertFalse(ResourcesService::reservesFirstLevel($row));
        $this->assertSame($freeBefore + 6, $resources->getFreeSupply(self::COLONY_ID));
        $response->assertJsonPath('freeSupply', $freeBefore + 6);
    }

    public function test_leveldown_is_free_of_charge(): void
    {
        $this->place(self::SCIENCELAB, 1, 2, -1);
        $advisors = $this->app->make(AdvisorService::class);
        $apBefore = $advisors->getAvailableActionPoints(self::COLONY_ID);
        $userBefore = (array) DB::table('user_resources')->where('user_id', self::USER_ID)->first();
        $colonyBefore = DB::table('colony_resources')->where('colony_id', self::COLONY_ID)->orderBy('resource_id')->get()->toArray();

        $this->leveldown(['building_id' => self::SCIENCELAB, 'instance_id' => 1])
            ->assertOk()
            ->assertJsonPath('apAvailable', $apBefore);

        $this->assertSame($apBefore, $advisors->getAvailableActionPoints(self::COLONY_ID));
        $this->assertEquals($colonyBefore, DB::table('colony_resources')->where('colony_id', self::COLONY_ID)->orderBy('resource_id')->get()->toArray());
        $userAfter = (array) DB::table('user_resources')->where('user_id', self::USER_ID)->first();
        $this->assertSame($userBefore['credits'], $userAfter['credits']);
    }

    public function test_response_carries_the_resourcebar_sync_data(): void
    {
        $this->place(self::HOUSING, 4, 1, -1);

        $response = $this->leveldown(['building_id' => self::HOUSING, 'instance_id' => 4])->assertOk();

        $response->assertJsonStructure([
            'ok', 'level', 'tile_released', 'released_tile', 'building',
            'apAvailable', 'regolith', 'werkstoffe', 'freeSupply',
            'colonists' => ['present', 'cap', 'homeless', 'departed', 'staffing_pct'],
        ]);
        $status = $this->app->make(ResourcesService::class)->colonistStatus(self::COLONY_ID);
        $response->assertJsonPath('colonists.cap', $status['cap']);
        $response->assertJsonPath('colonists.homeless', $status['homeless']);
    }

    public function test_command_center_cannot_go_below_level_one(): void
    {
        DB::table('colony_buildings')->where('colony_id', self::COLONY_ID)->where('building_id', self::CC)->update(['level' => 1]);

        $this->leveldown(['building_id' => self::CC, 'instance_id' => 1])
            ->assertStatus(422)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('error', 'min_level')
            ->assertJsonPath('message', __('colony.error_min_level'));

        $this->assertSame(1, (int) $this->row(self::CC, 1)->level);
    }

    public function test_command_center_above_one_can_be_levelled_down_without_a_tile(): void
    {
        $this->leveldown(['building_id' => self::CC, 'instance_id' => 1])
            ->assertOk()
            ->assertJsonPath('level', 2)
            ->assertJsonPath('tile_released', false);
    }

    public function test_unknown_instance_is_rejected(): void
    {
        $before = $this->housingSnapshot();

        $this->leveldown(['building_id' => self::HOUSING, 'instance_id' => 99])
            ->assertStatus(422)
            ->assertJsonPath('error', 'instance_not_found')
            ->assertJsonPath('message', __('colony.error_instance_not_found'));

        $this->assertEquals($before, $this->housingSnapshot());
    }

    public function test_foreign_instance_is_not_reachable(): void
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

        $this->leveldown(['building_id' => self::HOUSING, 'instance_id' => 7])
            ->assertStatus(422)
            ->assertJsonPath('error', 'instance_not_found');

        $this->assertSame(2, (int) $this->row(self::HOUSING, 7, $foreignColonyId)->level);
        $this->assertSame(0, (int) $this->row(self::HOUSING, 7, $foreignColonyId)->tile_x);
        $this->assertEquals($before, $this->housingSnapshot());
    }

    public function test_building_id_and_instance_id_are_required_integers(): void
    {
        $this->leveldown(['building_id' => self::HOUSING])->assertStatus(422)->assertJsonValidationErrors('instance_id');
        $this->leveldown(['instance_id' => 1])->assertStatus(422)->assertJsonValidationErrors('building_id');
        $this->leveldown(['building_id' => self::HOUSING, 'instance_id' => 'abc'])->assertStatus(422)->assertJsonValidationErrors('instance_id');
        $this->leveldown(['building_id' => 'x', 'instance_id' => 1])->assertStatus(422)->assertJsonValidationErrors('building_id');
    }

    // ── Bauabbruch (Owner decision 2026-09-24) ────────────────────────────────

    public function test_placed_construction_site_can_be_cancelled(): void
    {
        $this->place(self::SCIENCELAB, 1, 2, -1, 0);
        DB::table('colony_buildings')->where('colony_id', self::COLONY_ID)->where('building_id', self::SCIENCELAB)->update(['ap_spend' => 3]);

        $this->leveldown(['building_id' => self::SCIENCELAB, 'instance_id' => 1])
            ->assertOk()
            ->assertJsonPath('level', 0)
            ->assertJsonPath('construction_cancelled', true)
            ->assertJsonPath('tile_released', true)
            ->assertJsonPath('released_tile', ['q' => 2, 'r' => -1]);

        $row = $this->row(self::SCIENCELAB, 1);
        $this->assertSame(0, (int) $row->level);
        $this->assertNull($row->tile_x);
        $this->assertSame(0, (int) $row->ap_spend);
    }

    public function test_regular_leveldown_is_no_construction_cancel(): void
    {
        $this->place(self::HOUSING, 4, 1, -1);

        $this->leveldown(['building_id' => self::HOUSING, 'instance_id' => 4])
            ->assertOk()
            ->assertJsonPath('construction_cancelled', false);
    }

    public function test_unplaced_level_zero_building_is_rejected_as_not_placed(): void
    {
        DB::table('colony_buildings')->where('colony_id', self::COLONY_ID)->where('building_id', self::SCIENCELAB)->update(['level' => 0, 'tile_x' => null, 'tile_y' => null]);

        $this->leveldown(['building_id' => self::SCIENCELAB, 'instance_id' => 1])
            ->assertStatus(422)
            ->assertJsonPath('error', 'not_placed')
            ->assertJsonPath('message', __('colony.error_not_placed'));
    }

    // ── Bestandsschutz + no trust event (Owner decision 2026-09-24) ───────────

    /**
     * The Rückbau request itself touches only the addressed row. Rules that already
     * act on the current state elsewhere (e.g. in the tick) are not part of this.
     */
    public function test_leveldown_leaves_dependent_rows_untouched(): void
    {
        $this->place(self::HANGAR, 1, 3, -2);
        $snapshot = fn () => [
            'advisors' => DB::table('advisors')->orderBy('id')->get()->toArray(),
            'ships' => DB::table('colony_ships')->orderBy('id')->get()->toArray(),
            'missions' => DB::table('colony_hangar_missions')->orderBy('id')->get()->toArray(),
            'tiles' => DB::table('colony_tiles')->where('colony_id', self::COLONY_ID)->orderBy('q')->orderBy('r')->get()->toArray(),
            'researches' => DB::table('colony_researches')->where('colony_id', self::COLONY_ID)->orderBy('research_id')->get()->toArray(),
            'other_buildings' => DB::table('colony_buildings')->where('colony_id', self::COLONY_ID)
                ->whereNotIn('building_id', [self::CC, self::HANGAR])->orderBy('building_id')->orderBy('instance_id')->get()->toArray(),
        ];
        $before = $snapshot();

        $this->leveldown(['building_id' => self::CC, 'instance_id' => 1])->assertOk();
        $this->leveldown(['building_id' => self::HANGAR, 'instance_id' => 1])->assertOk();

        $this->assertEquals($before, $snapshot());
    }

    public function test_leveldown_creates_no_trust_event(): void
    {
        $this->place(self::SCIENCELAB, 1, 2, -1);
        $before = DB::table('trust_events')->count();

        $this->leveldown(['building_id' => self::SCIENCELAB, 'instance_id' => 1])->assertOk();

        $this->assertSame($before, DB::table('trust_events')->count());
    }

    // ── Preview for the confirmation modal ────────────────────────────────────

    public function test_preview_of_a_housing_leveldown_names_capacity_loss_and_homeless(): void
    {
        $this->place(self::HOUSING, 4, 1, -1);
        DB::table('colony_buildings')->where('colony_id', self::COLONY_ID)->where('building_id', self::HOUSING)->where('instance_id', 4)->update(['ap_spend' => 2]);
        $this->fillCapExactly();

        $this->preview(['building_id' => self::HOUSING, 'instance_id' => 4])
            ->assertOk()
            ->assertExactJson([
                'ok' => true,
                'building_id' => self::HOUSING,
                'instance_id' => 4,
                'current_level' => 3,
                'new_level' => 2,
                'construction_cancelled' => false,
                'ap_forfeited' => 2,
                'freed_workplaces' => 0,
                'capacity_loss' => self::HOUSING_CAP_PER_LEVEL,
                'homeless_now' => 0,
                'homeless_after' => self::HOUSING_CAP_PER_LEVEL,
                'tile_released' => false,
                'released_tile' => null,
            ]);
    }

    public function test_preview_of_a_construction_cancel(): void
    {
        $this->place(self::SCIENCELAB, 1, 2, -1, 0);
        DB::table('colony_buildings')->where('colony_id', self::COLONY_ID)->where('building_id', self::SCIENCELAB)->update(['ap_spend' => 3]);

        $this->preview(['building_id' => self::SCIENCELAB, 'instance_id' => 1])
            ->assertOk()
            ->assertJsonPath('current_level', 0)
            ->assertJsonPath('new_level', 0)
            ->assertJsonPath('construction_cancelled', true)
            ->assertJsonPath('ap_forfeited', 3)
            ->assertJsonPath('freed_workplaces', 6)
            ->assertJsonPath('capacity_loss', 0)
            ->assertJsonPath('tile_released', true)
            ->assertJsonPath('released_tile', ['q' => 2, 'r' => -1]);
    }

    public function test_preview_changes_nothing(): void
    {
        $this->place(self::SCIENCELAB, 1, 2, -1);
        $before = DB::table('colony_buildings')->orderBy('colony_id')->orderBy('building_id')->orderBy('instance_id')->get()->toArray();

        $this->preview(['building_id' => self::SCIENCELAB, 'instance_id' => 1])->assertOk();

        $this->assertEquals($before, DB::table('colony_buildings')->orderBy('colony_id')->orderBy('building_id')->orderBy('instance_id')->get()->toArray());
    }

    /**
     * Owner rule "angezeigte Zahl = wirkende Zahl": the previewed freed workplaces
     * show up in the free supply right after the Rückbau, the previewed capacity
     * loss in the cap the next Sol recalculates, and the previewed homeless count
     * is what the colonist status reports with that cap.
     */
    public function test_preview_matches_the_actual_effect(): void
    {
        $tick = 11400;
        $resources = $this->app->make(ResourcesService::class);
        $this->place(self::HOUSING, 4, 1, -1);
        $this->place(self::SCIENCELAB, 1, 2, -1);
        $this->fillCapExactly();
        $capBefore = (int) DB::table('user_resources')->where('user_id', self::USER_ID)->value('supply');

        $freeBefore = $resources->getFreeSupply(self::COLONY_ID);
        $sciencelab = $this->preview(['building_id' => self::SCIENCELAB, 'instance_id' => 1])->assertOk()->json();
        $this->leveldown(['building_id' => self::SCIENCELAB, 'instance_id' => 1])
            ->assertOk()
            ->assertJsonPath('level', $sciencelab['new_level']);
        $this->assertSame($freeBefore + $sciencelab['freed_workplaces'], $resources->getFreeSupply(self::COLONY_ID));

        $housing = $this->preview(['building_id' => self::HOUSING, 'instance_id' => 4])->assertOk()->json();
        $this->assertGreaterThan(0, $housing['homeless_after'], 'precondition: the housing loss leaves colonists homeless');
        $this->leveldown(['building_id' => self::HOUSING, 'instance_id' => 4])
            ->assertOk()
            ->assertJsonPath('level', $housing['new_level']);

        // A regular Sol recalculates the cap (GameTick::calculateSupply()).
        Artisan::call('game:tick', ['--tick' => $tick]);

        $this->assertSame($capBefore - $housing['capacity_loss'], (int) DB::table('user_resources')->where('user_id', self::USER_ID)->value('supply'));
        $this->assertSame($housing['homeless_after'], $resources->colonistStatus(self::COLONY_ID)['homeless']);
    }

    /**
     * The stored cap (user_resources.supply) lags behind within a Sol: a housing
     * upgrade raises the cap only when the next Sol recalculates it. homeless_now
     * and homeless_after must both be measured against that recalculated cap, so
     * the comparison shows the Rückbau's effect alone — and each equals what a real
     * Sol produces without resp. with the Rückbau.
     */
    public function test_preview_compares_against_the_next_sol_cap_when_stored_cap_is_stale(): void
    {
        $tick = 11400;
        $resources = $this->app->make(ResourcesService::class);
        $this->place(self::HOUSING, 4, 1, -1);
        $this->fillCapExactly();

        // Housing upgrade within the Sol, its new capacity already filled by workplaces;
        // the stored cap still predates both.
        DB::table('colony_buildings')->where('colony_id', self::COLONY_ID)->where('building_id', self::HOUSING)->where('instance_id', 5)->increment('level');
        DB::table('buildings')->where('id', self::BAR)->increment('supply_cost', self::HOUSING_CAP_PER_LEVEL);
        $this->assertGreaterThan(0, $resources->colonistStatus(self::COLONY_ID)['homeless'], 'precondition: stored cap is stale');

        $preview = $this->preview(['building_id' => self::HOUSING, 'instance_id' => 4])->assertOk()->json();
        $this->assertSame(0, $preview['homeless_now']);
        $this->assertSame(self::HOUSING_CAP_PER_LEVEL, $preview['homeless_after']);
        $this->assertSame(self::HOUSING_CAP_PER_LEVEL, $preview['capacity_loss']);

        DB::beginTransaction();
        Artisan::call('game:tick', ['--tick' => $tick]);
        $this->assertSame($preview['homeless_now'], $resources->colonistStatus(self::COLONY_ID)['homeless'], 'next Sol without Rückbau');
        DB::rollBack();

        $this->leveldown(['building_id' => self::HOUSING, 'instance_id' => 4])->assertOk();
        Artisan::call('game:tick', ['--tick' => $tick]);
        $this->assertSame($preview['homeless_after'], $resources->colonistStatus(self::COLONY_ID)['homeless'], 'next Sol with Rückbau');
    }

    public function test_preview_rejects_what_the_leveldown_rejects(): void
    {
        DB::table('colony_buildings')->where('colony_id', self::COLONY_ID)->where('building_id', self::CC)->update(['level' => 1]);

        $this->preview(['building_id' => self::CC, 'instance_id' => 1])
            ->assertStatus(422)
            ->assertJsonPath('error', 'min_level');
        $this->preview(['building_id' => self::HOUSING, 'instance_id' => 99])
            ->assertStatus(422)
            ->assertJsonPath('error', 'instance_not_found');
        $this->preview(['building_id' => self::HOUSING])
            ->assertStatus(422)
            ->assertJsonValidationErrors('instance_id');
    }

    public function test_guests_cannot_preview(): void
    {
        $this->getJson(route('colony.building.leveldown-preview', ['building_id' => self::HOUSING, 'instance_id' => 4]))
            ->assertUnauthorized();
    }

    public function test_new_error_codes_have_translations(): void
    {
        foreach (['de', 'en'] as $locale) {
            foreach (['min_level', 'instance_not_found', 'not_placed'] as $code) {
                $key = "colony.error_{$code}";
                $this->assertNotSame($key, __($key, [], $locale), "Missing {$locale} translation for {$key}");
            }
        }
    }

    public function test_guests_cannot_level_down(): void
    {
        $this->postJson(route('colony.building.leveldown'), ['building_id' => self::HOUSING, 'instance_id' => 4])
            ->assertUnauthorized();

        $this->assertSame(3, (int) $this->row(self::HOUSING, 4)->level);
    }
}
