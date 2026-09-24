<?php

namespace Tests\Feature\Console;

use App\Enums\BuildingId;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\CreatesForeignColony;
use Tests\TestCase;

class ValidateColonyTest extends TestCase
{
    use CreatesForeignColony;
    use RefreshDatabase;

    private const COLONY_ID = 1;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
    }

    public function test_unknown_colony_id_reports_not_found_and_fails(): void
    {
        $this->artisan('game:validate-colony', ['colony_id' => 999999])
            ->expectsOutputToContain('Colony 999999 not found.')
            ->assertExitCode(1);
    }

    public function test_healthy_colony_exits_zero_with_no_errors(): void
    {
        // Bring supply usage within cap and CC to a healthy level for a clean run.
        DB::table('user_resources')->where('user_id', 3)->update(['supply' => 999]);
        DB::table('colony_resources')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'resource_id' => 12],
            ['amount' => 25]
        );

        $this->artisan('game:validate-colony', ['colony_id' => self::COLONY_ID])
            ->assertExitCode(0);
    }

    public function test_missing_active_run_warns(): void
    {
        DB::table('runs')->where('colony_id', self::COLONY_ID)->delete();
        DB::table('user_resources')->where('user_id', 3)->update(['supply' => 999]);

        $this->artisan('game:validate-colony', ['colony_id' => self::COLONY_ID])
            ->expectsOutputToContain('No active run for colony')
            ->assertExitCode(0);
    }

    public function test_supply_overrun_is_an_error(): void
    {
        DB::table('user_resources')->where('user_id', 3)->update(['supply' => 0]);

        $this->artisan('game:validate-colony', ['colony_id' => self::COLONY_ID])
            ->expectsOutputToContain('Supply overrun')
            ->assertExitCode(1);
    }

    public function test_supply_usage_counts_level_zero_reserves(): void
    {
        // Placed level-0 buildings reserve their first level (GDD §6 "Supply als Bau-Gate").
        DB::table('buildings')->update(['supply_cost' => 0]);
        DB::table('buildings')->where('id', 52)->update(['supply_cost' => 7]);
        DB::table('colony_buildings')->where('colony_id', self::COLONY_ID)->where('building_id', 52)
            ->update(['level' => 0, 'tile_x' => 1, 'tile_y' => 0]);
        DB::table('user_resources')->where('user_id', 3)->update(['supply' => 5]);

        $this->artisan('game:validate-colony', ['colony_id' => self::COLONY_ID])
            ->expectsOutputToContain('Supply overrun: usage=7 > cap=5')
            ->assertExitCode(1);
    }

    public function test_missing_command_center_is_an_error(): void
    {
        DB::table('colony_buildings')
            ->where('colony_id', self::COLONY_ID)
            ->where('building_id', BuildingId::CommandCenter->value)
            ->update(['level' => 0]);

        $this->artisan('game:validate-colony', ['colony_id' => self::COLONY_ID])
            ->expectsOutputToContain('CommandCenter missing or level 0')
            ->assertExitCode(1);
    }

    public function test_missing_trust_resource_row_warns(): void
    {
        DB::table('colony_resources')
            ->where('colony_id', self::COLONY_ID)->where('resource_id', 12)->delete();
        DB::table('user_resources')->where('user_id', 3)->update(['supply' => 999]);

        $this->artisan('game:validate-colony', ['colony_id' => self::COLONY_ID])
            ->expectsOutputToContain('Trust resource row missing')
            ->assertExitCode(0);
    }

    public function test_critically_low_trust_warns(): void
    {
        DB::table('colony_resources')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'resource_id' => 12],
            ['amount' => -60]
        );
        DB::table('user_resources')->where('user_id', 3)->update(['supply' => 999]);

        $this->artisan('game:validate-colony', ['colony_id' => self::COLONY_ID])
            ->expectsOutputToContain('Trust critically low: -60')
            ->assertExitCode(0);
    }

    public function test_current_tick_exceeding_limit_is_an_error(): void
    {
        DB::table('runs')->where('colony_id', self::COLONY_ID)->where('status', 'active')
            ->update(['current_tick' => 500, 'settings' => json_encode(['tick_limit' => 100])]);

        $this->artisan('game:validate-colony', ['colony_id' => self::COLONY_ID])
            ->expectsOutputToContain('exceeds tick_limit=100')
            ->assertExitCode(1);
    }

    public function test_current_tick_within_limit_is_ok(): void
    {
        DB::table('runs')->where('colony_id', self::COLONY_ID)->where('status', 'active')
            ->update(['current_tick' => 5, 'settings' => json_encode(['tick_limit' => 100])]);
        DB::table('user_resources')->where('user_id', 3)->update(['supply' => 999]);

        $this->artisan('game:validate-colony', ['colony_id' => self::COLONY_ID])
            ->expectsOutputToContain('current_tick=5 / 100')
            ->assertExitCode(0);
    }

    public function test_without_colony_id_checks_all_colonies(): void
    {
        // Colony 1 healthy (see test_healthy_colony_exits_zero_with_no_errors), plus a
        // second player's colony without a CommandCenter: only a run that really
        // iterates over all colonies can surface that colony's error.
        DB::table('user_resources')->where('user_id', 3)->update(['supply' => 999]);
        DB::table('colony_resources')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'resource_id' => 12],
            ['amount' => 25]
        );
        $foreign = $this->createForeignColony(buildings: []);

        $this->artisan('game:validate-colony')
            ->expectsOutputToContain('(id='.self::COLONY_ID.')')
            ->expectsOutputToContain('(id='.$foreign['colony_id'].')')
            ->expectsOutputToContain('CommandCenter missing or level 0')
            ->assertExitCode(1);
    }
}
