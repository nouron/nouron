<?php

namespace Tests\Feature\Console;

use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ColonySeedDemoTest extends TestCase
{
    use RefreshDatabase;

    private const COLONY_ID = 1;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
    }

    public function test_unknown_colony_fails(): void
    {
        $this->artisan('colony:seed-demo', ['colony_id' => 999999])
            ->expectsOutputToContain('Colony 999999 not found.')
            ->assertExitCode(1);
    }

    public function test_seeds_37_tiles_across_rings_0_to_3(): void
    {
        $this->artisan('colony:seed-demo', ['colony_id' => self::COLONY_ID])->assertExitCode(0);

        $this->assertSame(37, DB::table('colony_tiles')->where('colony_id', self::COLONY_ID)->count());
        $this->assertEqualsCanonicalizing(
            [0, 1, 1, 1, 1, 1, 1, 2, 2, 2, 2, 2, 2, 2, 2, 2, 2, 2, 2, 3, 3, 3, 3, 3, 3, 3, 3, 3, 3, 3, 3, 3, 3, 3, 3, 3, 3],
            DB::table('colony_tiles')->where('colony_id', self::COLONY_ID)->pluck('ring')->all()
        );
    }

    public function test_command_center_tile_is_empty_terrain(): void
    {
        $this->artisan('colony:seed-demo', ['colony_id' => self::COLONY_ID])->assertExitCode(0);

        $cc = DB::table('colony_tiles')->where('colony_id', self::COLONY_ID)->where('q', 0)->where('r', 0)->first();
        $this->assertSame('terrain_empty', $cc->tile_type);
        $this->assertSame(0, $cc->resource_max);
    }

    public function test_harvester_tile_is_regolith_rich_with_resources(): void
    {
        $this->artisan('colony:seed-demo', ['colony_id' => self::COLONY_ID])->assertExitCode(0);

        $tile = DB::table('colony_tiles')->where('colony_id', self::COLONY_ID)->where('q', 3)->where('r', 0)->first();
        $this->assertSame('regolith_rich', $tile->tile_type);
        $this->assertGreaterThan(0, $tile->resource_max);
        $this->assertGreaterThan(0, $tile->resource_amount);
        $this->assertLessThan($tile->resource_max, $tile->resource_amount);
    }

    public function test_forced_ring3_events_are_placed(): void
    {
        $this->artisan('colony:seed-demo', ['colony_id' => self::COLONY_ID])->assertExitCode(0);

        $events = DB::table('colony_tiles')
            ->where('colony_id', self::COLONY_ID)->where('ring', 3)->whereNotNull('event_type')
            ->pluck('event_type', DB::raw("q || ',' || r"));

        $this->assertSame('event_ruin', DB::table('colony_tiles')
            ->where('colony_id', self::COLONY_ID)->where('q', 0)->where('r', 3)->value('event_type'));
        $this->assertSame('event_crystal', DB::table('colony_tiles')
            ->where('colony_id', self::COLONY_ID)->where('q', -3)->where('r', 2)->value('event_type'));
        $this->assertSame('event_cache', DB::table('colony_tiles')
            ->where('colony_id', self::COLONY_ID)->where('q', 0)->where('r', -3)->value('event_type'));
        $this->assertSame('event_wreck', DB::table('colony_tiles')
            ->where('colony_id', self::COLONY_ID)->where('q', 2)->where('r', -3)->value('event_type'));
    }

    public function test_all_tiles_are_explored_and_ring3_partially_deep_scanned(): void
    {
        $this->artisan('colony:seed-demo', ['colony_id' => self::COLONY_ID])->assertExitCode(0);

        $this->assertSame(0, DB::table('colony_tiles')
            ->where('colony_id', self::COLONY_ID)->where('is_explored', false)->count());

        $ring3DeepScanned = DB::table('colony_tiles')
            ->where('colony_id', self::COLONY_ID)->where('ring', 3)->where('is_deep_scanned', true)->count();
        // ~67% of 18 ring-3 tiles per the seed formula — just assert it's a mix, not all-or-nothing.
        $this->assertGreaterThan(0, $ring3DeepScanned);
        $this->assertLessThan(18, $ring3DeepScanned);
    }

    public function test_building_placements_and_levels_are_applied(): void
    {
        $this->artisan('colony:seed-demo', ['colony_id' => self::COLONY_ID])->assertExitCode(0);

        $cc = DB::table('colony_buildings')->where('colony_id', self::COLONY_ID)->where('building_id', 25)->first();
        $this->assertSame(0, $cc->tile_x);
        $this->assertSame(0, $cc->tile_y);
        $this->assertSame(5, $cc->level);

        $harvester = DB::table('colony_buildings')->where('colony_id', self::COLONY_ID)->where('building_id', 27)->first();
        $this->assertSame(3, $harvester->tile_x);
        $this->assertSame(1, $harvester->level);
    }

    public function test_unplaced_buildings_are_cleared_to_available(): void
    {
        // infirmary (46) is deliberately left unplaced.
        DB::table('colony_buildings')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'building_id' => 46],
            ['tile_x' => 9, 'tile_y' => 9, 'level' => 3, 'status_points' => 20, 'ap_spend' => 0]
        );

        $this->artisan('colony:seed-demo', ['colony_id' => self::COLONY_ID])->assertExitCode(0);

        $infirmary = DB::table('colony_buildings')->where('colony_id', self::COLONY_ID)->where('building_id', 46)->first();
        $this->assertNull($infirmary->tile_x);
        $this->assertNull($infirmary->tile_y);
        $this->assertSame(0, $infirmary->level);
    }

    public function test_ensures_pilot_advisor_exists(): void
    {
        DB::table('advisors')->where('colony_id', self::COLONY_ID)
            ->where('personell_id', config('advisors.pilot.id'))->delete();

        $this->artisan('colony:seed-demo', ['colony_id' => self::COLONY_ID])->assertExitCode(0);

        $this->assertSame(1, DB::table('advisors')
            ->where('colony_id', self::COLONY_ID)->where('personell_id', config('advisors.pilot.id'))->count());
    }

    /**
     * KNOWN BUG (found 2026-07-24, not fixed here — out of scope for a coverage
     * pass): the `advisors_colony_personell_unique` partial index
     * (colony_id, personell_id) that insertOrIgnore() relies on for idempotency
     * no longer exists on the current schema — lost during a later table rebuild
     * (galaxy/fleet removal). ensurePilotAdvisor() is NOT actually idempotent;
     * this test documents the current (buggy) behavior rather than asserting the
     * intended one, so it doesn't silently regress further.
     */
    public function test_ensure_pilot_advisor_currently_duplicates_on_rerun_known_bug(): void
    {
        DB::table('advisors')->where('colony_id', self::COLONY_ID)
            ->where('personell_id', config('advisors.pilot.id'))->delete();

        $this->artisan('colony:seed-demo', ['colony_id' => self::COLONY_ID])->assertExitCode(0);
        $this->artisan('colony:seed-demo', ['colony_id' => self::COLONY_ID])->assertExitCode(0);

        $this->assertSame(2, DB::table('advisors')
            ->where('colony_id', self::COLONY_ID)->where('personell_id', config('advisors.pilot.id'))->count());
    }

    public function test_rerun_clears_previous_tiles_instead_of_duplicating(): void
    {
        $this->artisan('colony:seed-demo', ['colony_id' => self::COLONY_ID])->assertExitCode(0);
        $this->artisan('colony:seed-demo', ['colony_id' => self::COLONY_ID])->assertExitCode(0);

        $this->assertSame(37, DB::table('colony_tiles')->where('colony_id', self::COLONY_ID)->count());
    }

    public function test_default_path_all_builds_all_three_sol2_path_buildings(): void
    {
        $this->artisan('colony:seed-demo', ['colony_id' => self::COLONY_ID])->assertExitCode(0);

        foreach ([52 => 'bar', 44 => 'hangar', 31 => 'sciencelab'] as $buildingId => $name) {
            $building = DB::table('colony_buildings')->where('colony_id', self::COLONY_ID)->where('building_id', $buildingId)->first();
            $this->assertNotNull($building->tile_x, "{$name} should be placed under --path=all");
            $this->assertGreaterThan(0, $building->level, "{$name} should have a level under --path=all");
        }
    }

    public function test_path_cantina_builds_only_bar_leaves_hangar_and_lab_available(): void
    {
        $this->artisan('colony:seed-demo', ['colony_id' => self::COLONY_ID, '--path' => 'cantina'])->assertExitCode(0);

        $bar = DB::table('colony_buildings')->where('colony_id', self::COLONY_ID)->where('building_id', 52)->first();
        $this->assertNotNull($bar->tile_x);
        $this->assertGreaterThan(0, $bar->level);

        foreach ([44, 31] as $buildingId) {
            $building = DB::table('colony_buildings')->where('colony_id', self::COLONY_ID)->where('building_id', $buildingId)->first();
            $this->assertNull($building->tile_x);
            $this->assertSame(0, $building->level);
        }
    }

    public function test_path_hangar_builds_only_hangar_leaves_cantina_and_lab_available(): void
    {
        $this->artisan('colony:seed-demo', ['colony_id' => self::COLONY_ID, '--path' => 'hangar'])->assertExitCode(0);

        $hangar = DB::table('colony_buildings')->where('colony_id', self::COLONY_ID)->where('building_id', 44)->first();
        $this->assertNotNull($hangar->tile_x);
        $this->assertGreaterThan(0, $hangar->level);

        foreach ([52, 31] as $buildingId) {
            $building = DB::table('colony_buildings')->where('colony_id', self::COLONY_ID)->where('building_id', $buildingId)->first();
            $this->assertNull($building->tile_x);
            $this->assertSame(0, $building->level);
        }
    }

    public function test_path_lab_builds_only_sciencelab_leaves_cantina_and_hangar_available(): void
    {
        $this->artisan('colony:seed-demo', ['colony_id' => self::COLONY_ID, '--path' => 'lab'])->assertExitCode(0);

        $lab = DB::table('colony_buildings')->where('colony_id', self::COLONY_ID)->where('building_id', 31)->first();
        $this->assertNotNull($lab->tile_x);
        $this->assertGreaterThan(0, $lab->level);

        foreach ([52, 44] as $buildingId) {
            $building = DB::table('colony_buildings')->where('colony_id', self::COLONY_ID)->where('building_id', $buildingId)->first();
            $this->assertNull($building->tile_x);
            $this->assertSame(0, $building->level);
        }
    }

    public function test_invalid_path_option_fails(): void
    {
        $this->artisan('colony:seed-demo', ['colony_id' => self::COLONY_ID, '--path' => 'nonsense'])
            ->expectsOutputToContain('Invalid --path')
            ->assertExitCode(1);
    }
}
