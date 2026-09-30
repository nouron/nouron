<?php

namespace Tests\Feature\GameTick;

use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\CreatesForeignColony;
use Tests\TestCase;

/**
 * R18 — `game:tick --run=X` (triggered by one player's /sol/next) must only
 * simulate that run's colony and user. Every other player's colony stays
 * untouched until *they* advance their own Sol.
 *
 * Fixture: Bart (user 3) owns colony 1 with active run 1 (TestSeeder).
 * A second player (user 20, colony 2) gets their own active run plus state
 * that every tick step would otherwise touch.
 */
class GameTickRunIsolationTest extends TestCase
{
    use CreatesForeignColony;
    use RefreshDatabase;

    private const BART_RUN_ID = 1;

    private const FOREIGN_RUN_ID = 2;

    private const TICK = 20;

    private int $foreignColonyId;

    private int $foreignUserId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();

        DB::table('runs')->where('id', self::BART_RUN_ID)->update(['current_tick' => self::TICK]);

        // CC, bar (52), Uplink and a decaying ordinary building.
        $foreign = $this->createForeignColony([25 => 2, 52 => 1, 27 => 1, 28 => 2]);
        $this->foreignColonyId = $foreign['colony_id'];
        $this->foreignUserId = $foreign['user_id'];

        DB::table('runs')->insert([
            'id' => self::FOREIGN_RUN_ID,
            'user_id' => $this->foreignUserId,
            'colony_id' => $this->foreignColonyId,
            'current_tick' => 3,
            'status' => 'active',
            'phase' => 1,
            'nexus_debt' => 0,
            'started_at' => now(),
        ]);

        foreach ([3 => 100, 4 => 20, 5 => 200, 12 => 5] as $resourceId => $amount) {
            DB::table('colony_resources')->insert([
                'colony_id' => $this->foreignColonyId,
                'resource_id' => $resourceId,
                'amount' => $amount,
            ]);
        }

        DB::table('colony_researches')->insert([
            'colony_id' => $this->foreignColonyId,
            'research_id' => 9901,
            'level' => 1,
            'status_points' => 20,
            'ap_spend' => 0,
        ]);

        // Rank-3 advisor with no credits to pay for it → would create debt.
        DB::table('user_resources')->where('user_id', $this->foreignUserId)->update(['credits' => 0]);
        DB::table('advisors')->insert([
            'user_id' => $this->foreignUserId,
            'colony_id' => $this->foreignColonyId,
            'personell_id' => 35,
            'rank' => 3,
            'active_ticks' => 7,
        ]);

        // Due deliveries for the foreign colony (tick 20 >= deliver_at_tick).
        DB::table('nexus_imports')->insert([
            'colony_id' => $this->foreignColonyId,
            'resource_id' => 3,
            'amount' => 50,
            'deliver_at_tick' => 1,
        ]);
        DB::table('colony_ships')->insert([
            'colony_id' => $this->foreignColonyId,
            'ship_id' => 85,
            'level' => 0,
            'status_points' => 10,
            'ap_spend' => 0,
            'hangar_instance_id' => null,
            'ship_state' => 'building',
            'deliver_at_tick' => 1,
            'pending_until_tick' => null,
        ]);
    }

    /** @return array<string, mixed> */
    private function foreignSnapshot(): array
    {
        $colony = $this->foreignColonyId;
        $user = $this->foreignUserId;

        return [
            'buildings' => DB::table('colony_buildings')->where('colony_id', $colony)
                ->orderBy('building_id')->get(['building_id', 'level', 'status_points'])->toArray(),
            'resources' => DB::table('colony_resources')->where('colony_id', $colony)
                ->orderBy('resource_id')->pluck('amount', 'resource_id')->all(),
            'researches' => DB::table('colony_researches')->where('colony_id', $colony)
                ->get(['research_id', 'level', 'status_points'])->toArray(),
            'user_resources' => (array) DB::table('user_resources')->where('user_id', $user)->first(['credits', 'supply']),
            'colony_row' => (array) DB::table('glx_colonies')->where('id', $colony)
                ->first(['hunger_streak', 'overcap_streak', 'overcap_departed']),
            'advisor_ticks' => DB::table('advisors')->where('colony_id', $colony)->pluck('active_ticks')->all(),
            'run' => (array) DB::table('runs')->where('id', self::FOREIGN_RUN_ID)->first(['current_tick', 'nexus_debt', 'phase', 'status']),
            'nexus_imports' => DB::table('nexus_imports')->where('colony_id', $colony)->count(),
            'ships' => DB::table('colony_ships')->where('colony_id', $colony)->pluck('ship_state')->all(),
            'bar_offers' => DB::table('bar_offers')->where('colony_id', $colony)->count(),
            'merchant_visits' => DB::table('merchant_visits')->where('colony_id', $colony)->count(),
            'events' => DB::table('colony_log')->where('user', $user)->count(),
        ];
    }

    public function test_tick_of_one_run_leaves_other_players_colony_untouched(): void
    {
        $before = $this->foreignSnapshot();

        $exit = Artisan::call('game:tick', ['--run' => self::BART_RUN_ID]);

        $this->assertSame(0, $exit, Artisan::output());
        $after = $this->foreignSnapshot();

        foreach ($before as $key => $value) {
            $this->assertEquals($value, $after[$key], "Foreign colony state '{$key}' changed by another player's tick");
        }
    }

    public function test_tick_still_processes_own_colony(): void
    {
        $creditsBefore = (int) DB::table('user_resources')->where('user_id', 3)->value('credits');

        Artisan::call('game:tick', ['--run' => self::BART_RUN_ID]);

        // Bart's own colony got its passive Nexus subsidy this Sol.
        $this->assertNotSame($creditsBefore, (int) DB::table('user_resources')->where('user_id', 3)->value('credits'));
    }
}
