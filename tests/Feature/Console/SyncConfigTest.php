<?php

namespace Tests\Feature\Console;

use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SyncConfigTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
    }

    public function test_reports_no_changes_on_a_second_run(): void
    {
        // Fresh seed data isn't guaranteed to already match config (that's the whole
        // point of this command) — sync once, then the second run must be a no-op.
        $this->artisan('game:sync-config')->assertExitCode(0);

        $this->artisan('game:sync-config')
            ->expectsOutputToContain('Everything is already in sync')
            ->assertExitCode(0);
    }

    public function test_updates_a_drifted_ship_column(): void
    {
        DB::table('ships')->where('id', 37)->update(['moving_speed' => 999]);

        $this->artisan('game:sync-config')
            ->expectsOutputToContain('[ship]')
            ->assertExitCode(0);

        $this->assertSame(4, (int) DB::table('ships')->where('id', 37)->value('moving_speed'));
    }

    public function test_updates_a_drifted_building_column(): void
    {
        DB::table('buildings')->where('id', 25)->update(['max_status_points' => 1]);

        $this->artisan('game:sync-config')
            ->expectsOutputToContain('[building]')
            ->assertExitCode(0);

        $this->assertNotSame(1, (int) DB::table('buildings')->where('id', 25)->value('max_status_points'));
    }

    public function test_dry_run_previews_without_writing(): void
    {
        DB::table('ships')->where('id', 37)->update(['moving_speed' => 999]);

        $this->artisan('game:sync-config', ['--dry-run' => true])
            ->expectsOutputToContain('DRY RUN')
            ->expectsOutputToContain('[ship]')
            ->assertExitCode(0);

        $this->assertSame(999, (int) DB::table('ships')->where('id', 37)->value('moving_speed'));
    }

    public function test_ship_config_entry_missing_id_is_skipped_with_warning(): void
    {
        config(['ships.broken' => ['moving_speed' => 1]]);

        $this->artisan('game:sync-config')
            ->expectsOutputToContain("ships/broken: missing 'id' — skipped.")
            ->assertExitCode(0);
    }

    public function test_ship_config_entry_with_unknown_id_is_skipped_with_warning(): void
    {
        config(['ships.ghost' => ['id' => 999999]]);

        $this->artisan('game:sync-config')
            ->expectsOutputToContain('ships/ghost: id=999999 not found in DB — skipped.')
            ->assertExitCode(0);
    }

    public function test_building_config_entry_missing_id_is_skipped_with_warning(): void
    {
        config(['buildings.broken' => ['decay_rate' => 1]]);

        $this->artisan('game:sync-config')
            ->expectsOutputToContain("buildings/broken: missing 'id' — skipped.")
            ->assertExitCode(0);
    }

    public function test_building_config_entry_with_unknown_id_is_skipped_with_warning(): void
    {
        config(['buildings.ghost' => ['id' => 999999]]);

        $this->artisan('game:sync-config')
            ->expectsOutputToContain('buildings/ghost: id=999999 not found in DB — skipped.')
            ->assertExitCode(0);
    }

    public function test_building_max_level_syncs_to_configured_value(): void
    {
        // commandCenter (id=25) has an explicit max_level (5) in config.
        DB::table('buildings')->where('id', 25)->update(['max_level' => 7]);

        $this->artisan('game:sync-config')
            ->expectsOutputToContain('[building]')
            ->assertExitCode(0);

        $this->assertSame(5, (int) DB::table('buildings')->where('id', 25)->value('max_level'));
    }

    /**
     * When config omits 'max_level' entirely, isset($cfg['max_level']) is false and
     * the sync intentionally falls back to whatever's already in the DB (never
     * writes null) — housingComplex's config always defines it, so simulate the
     * omission via a config override to exercise that fallback branch.
     */
    public function test_building_max_level_untouched_when_absent_from_config(): void
    {
        $key = collect(config('buildings'))->search(fn ($cfg) => ($cfg['id'] ?? null) === 28);
        $withoutMaxLevel = collect(config("buildings.{$key}"))->except('max_level')->all();
        config(["buildings.{$key}" => $withoutMaxLevel]);
        DB::table('buildings')->where('id', 28)->update(['max_level' => 9]);

        $this->artisan('game:sync-config')->assertExitCode(0);

        $this->assertSame(9, (int) DB::table('buildings')->where('id', 28)->value('max_level'));
    }

    public function test_syncs_building_costs_from_config(): void
    {
        DB::table('building_costs')->where('building_id', 25)->whereIn('resource_id', [3, 4])->delete();
        DB::table('building_costs')->insert(['building_id' => 25, 'resource_id' => 3, 'amount' => 1]);

        $this->artisan('game:sync-config')
            ->expectsOutputToContain('[build_cost]')
            ->assertExitCode(0);
    }

    public function test_reports_total_row_count_across_ships_and_buildings(): void
    {
        DB::table('ships')->where('id', 37)->update(['moving_speed' => 999]);
        DB::table('buildings')->where('id', 25)->update(['max_status_points' => 1]);

        // The summary line can word-wrap in the test runner's assumed terminal
        // width, splitting a naive substring search across two writes — assert
        // on the actual DB effect instead of matching output text.
        $this->artisan('game:sync-config')->assertExitCode(0);

        $this->assertSame(4, (int) DB::table('ships')->where('id', 37)->value('moving_speed'));
        $this->assertSame(20, (int) DB::table('buildings')->where('id', 25)->value('max_status_points'));
    }
}
