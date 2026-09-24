<?php

namespace Tests\Feature\Console;

use App\Services\ResourcesService;
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

    /**
     * Konsul-Handelsvertrag was removed (Owner-Entscheidung F3/A22, 2026-09-09) —
     * even with Cantina built and a Konsul assigned, the dry-run credits line
     * must never show a "contract" income component.
     */
    public function test_cantina_konsul_never_shows_contract_income(): void
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
            ->doesntExpectOutputToContain('contract')
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

    /** Stored cap = workplaces − $homeless − $departed. */
    private function setColonists(int $homeless, int $departed = 0, int $streak = 0): void
    {
        $breakdown = app(ResourcesService::class)->getSupplyBreakdown(self::COLONY_ID);
        $workplaces = $breakdown['cap'] - $breakdown['free'];
        DB::table('user_resources')->where('user_id', self::USER_ID)->update(['supply' => $workplaces - $homeless - $departed]);
        DB::table('glx_colonies')->where('id', self::COLONY_ID)->update(['overcap_streak' => $streak, 'overcap_departed' => $departed]);
    }

    /** A14 / GDD §7: over-capacity no longer accelerates decay. */
    public function test_decay_is_not_accelerated_when_over_capacity(): void
    {
        $this->setColonists(6);

        $this->artisan('game:tick-dry-run', ['--colony' => self::COLONY_ID])
            ->doesntExpectOutputToContain('decay ×')
            ->assertExitCode(0);
    }

    /**
     * A14: the dry run previews homeless colonists, the next streak and its trust
     * penalty, and the Sols until departure. Deadline 3, streak 1 → 2 (−3), departure in 3 Sol
     * (streak 2, streak 3, then the departure tick).
     */
    public function test_homeless_streak_penalty_and_departure_are_previewed(): void
    {
        config([
            'game.overcap.departure_after_sols' => 3,
            'game.overcap.trust_base_malus' => 2,
            'game.overcap.trust_step' => 1,
            'game.overcap.trust_cap' => 4,
        ]);
        $this->setColonists(6, 0, 1);

        $this->artisan('game:tick-dry-run', ['--colony' => self::COLONY_ID])
            ->expectsOutputToContain('Over-capacity: 6 homeless, streak 1 → 2, trust penalty next Sol: -3, departure in 3 Sol')
            ->assertExitCode(0);
    }

    public function test_departure_next_sol_is_previewed_once_the_deadline_is_reached(): void
    {
        config(['game.overcap.departure_after_sols' => 3]);
        $this->setColonists(6, 0, 3);

        $this->artisan('game:tick-dry-run', ['--colony' => self::COLONY_ID])
            ->expectsOutputToContain('Departure next Sol: 6 colonists leave')
            ->assertExitCode(0);
    }

    public function test_understaffing_is_previewed_with_the_staffing_share(): void
    {
        $this->setColonists(0, 8);
        $pct = (int) round(app(ResourcesService::class)->staffingShare(self::COLONY_ID) * 100);

        $this->artisan('game:tick-dry-run', ['--colony' => self::COLONY_ID])
            ->expectsOutputToContain("Understaffed: 8 workplaces unfilled, raw-material production at {$pct}%")
            ->assertExitCode(0);
    }
}
