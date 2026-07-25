<?php

namespace Tests\Feature\Console;

use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * GameTickDryRun had 0% coverage — a read-only diagnostic display, safe to
 * exercise fully (no DB writes, per its own docblock).
 */
class GameTickDryRunTest extends TestCase
{
    use RefreshDatabase;

    private const COLONY_ID = 1;

    private const USER_ID = 3;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
    }

    public function test_no_colonies_found_warns_and_fails(): void
    {
        $this->artisan('game:tick-dry-run', ['--colony' => 999999])
            ->expectsOutputToContain('No colonies found.')
            ->assertExitCode(1);
    }

    public function test_all_colonies_mode_renders_header(): void
    {
        $this->artisan('game:tick-dry-run')
            ->expectsOutputToContain('Tick Dry-Run')
            ->assertExitCode(0);
    }

    public function test_single_colony_filter_shows_only_that_colony(): void
    {
        $this->artisan('game:tick-dry-run', ['--colony' => self::COLONY_ID])
            ->expectsOutputToContain('ID:'.self::COLONY_ID)
            ->assertExitCode(0);
    }

    public function test_npc_colony_shows_npc_fallback_for_username(): void
    {
        DB::table('glx_colonies')->where('id', self::COLONY_ID)->update(['user_id' => null]);

        $this->artisan('game:tick-dry-run', ['--colony' => self::COLONY_ID])
            ->expectsOutputToContain('User: NPC')
            ->assertExitCode(0);
    }

    public function test_supply_cap_increase_is_shown(): void
    {
        DB::table('user_resources')->where('user_id', self::USER_ID)->update(['supply' => 1]);

        $this->artisan('game:tick-dry-run', ['--colony' => self::COLONY_ID])
            ->expectsOutputToContain('Supply cap:')
            ->assertExitCode(0);
    }

    public function test_supply_cap_decrease_when_command_center_missing(): void
    {
        DB::table('colony_buildings')->where('colony_id', self::COLONY_ID)->where('building_id', 25)
            ->update(['level' => 0]);
        DB::table('user_resources')->where('user_id', self::USER_ID)->update(['supply' => 50]);

        $this->artisan('game:tick-dry-run', ['--colony' => self::COLONY_ID])
            ->expectsOutputToContain('Supply cap:')
            ->assertExitCode(0);
    }

    public function test_cantina_contract_income_is_included_when_konsul_assigned(): void
    {
        DB::table('colony_buildings')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'building_id' => 52],
            ['level' => 1, 'status_points' => 20, 'ap_spend' => 0]
        );
        DB::table('advisors')->where('colony_id', self::COLONY_ID)->delete();
        DB::table('advisors')->insert([
            'user_id' => self::USER_ID,
            'personell_id' => config('advisors.trader.id', 92),
            'colony_id' => self::COLONY_ID,
            'rank' => 2,
            'active_ticks' => 0,
        ]);

        $this->artisan('game:tick-dry-run', ['--colony' => self::COLONY_ID])
            ->expectsOutputToContain('contract')
            ->assertExitCode(0);
    }

    public function test_resource_production_yield_is_shown_for_built_harvester(): void
    {
        DB::table('colony_buildings')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'building_id' => 27],
            ['level' => 3, 'status_points' => 20, 'ap_spend' => 0]
        );

        $this->artisan('game:tick-dry-run', ['--colony' => self::COLONY_ID])
            ->expectsOutputToContain('Regolith:')
            ->assertExitCode(0);
    }

    public function test_building_decay_level_down_flag_when_status_points_would_hit_zero(): void
    {
        DB::table('colony_buildings')->where('colony_id', self::COLONY_ID)->where('building_id', 27)
            ->update(['level' => 1, 'status_points' => 0.1]);

        $this->artisan('game:tick-dry-run', ['--colony' => self::COLONY_ID])
            ->expectsOutputToContain('LEVEL DOWN')
            ->assertExitCode(0);
    }

    public function test_building_decay_critical_flag_below_20_percent(): void
    {
        DB::table('colony_buildings')->where('colony_id', self::COLONY_ID)->where('building_id', 27)
            ->update(['level' => 1, 'status_points' => 3]);
        DB::table('buildings')->where('id', 27)->update(['decay_rate' => 0, 'max_status_points' => 20]);

        $this->artisan('game:tick-dry-run', ['--colony' => self::COLONY_ID])
            ->expectsOutputToContain('critical')
            ->assertExitCode(0);
    }

    public function test_building_decay_attention_flag_between_20_and_40_percent(): void
    {
        DB::table('colony_buildings')->where('colony_id', self::COLONY_ID)->where('building_id', 27)
            ->update(['level' => 1, 'status_points' => 6]);
        DB::table('buildings')->where('id', 27)->update(['decay_rate' => 0, 'max_status_points' => 20]);

        $this->artisan('game:tick-dry-run', ['--colony' => self::COLONY_ID])
            ->expectsOutputToContain('attention')
            ->assertExitCode(0);
    }

    public function test_overcap_decay_factor_applied_when_supply_exceeds_cap(): void
    {
        DB::table('user_resources')->where('user_id', self::USER_ID)->update(['supply' => 0]);

        $this->artisan('game:tick-dry-run', ['--colony' => self::COLONY_ID])
            ->expectsOutputToContain('Over supply cap')
            ->assertExitCode(0);
    }
}
