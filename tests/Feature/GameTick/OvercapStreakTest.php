<?php

namespace Tests\Feature\GameTick;

use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * A14 stage 1 (GDD §6 "Überkapazität — Konsequenzen"): GameTick tracks
 * glx_colonies.overcap_streak — +1 per Sol the colony is over its supply cap
 * (free < 0), reset to 0 as soon as it is back within cap — and writes a
 * colony_log entry on exactly two transitions: entering over-capacity
 * (streak 1) and the end of the grace period (first Sol with a trust penalty).
 *
 * Over-cap setup: all supply costs zeroed, then the Harvester (27, colony 1
 * level 1) gets a supply cost far above any possible cap (cap_max 200).
 * Within-cap: that supply cost set back to 0.
 *
 * Fixture: Colony 1 (Springfield), user_id=3. Uses tick numbers 12600–12699.
 */
class OvercapStreakTest extends TestCase
{
    use RefreshDatabase;

    private const COLONY_ID = 1;

    private const USER_ID = 3;

    private const HARVESTER = 27;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();

        DB::table('buildings')->update(['supply_cost' => 0]);
        DB::table('researches')->update(['supply_cost' => 0]);
        DB::table('ships')->update(['supply_cost' => 0]);
    }

    private function goOverCap(): void
    {
        DB::table('buildings')->where('id', self::HARVESTER)->update(['supply_cost' => 500]);
    }

    private function goWithinCap(): void
    {
        DB::table('buildings')->where('id', self::HARVESTER)->update(['supply_cost' => 0]);
    }

    private function streak(): int
    {
        return (int) DB::table('glx_colonies')->where('id', self::COLONY_ID)->value('overcap_streak');
    }

    private function tick(int $tick): void
    {
        Artisan::call('game:tick', ['--tick' => $tick]);
    }

    private function logCount(string $event): int
    {
        return DB::table('colony_log')
            ->where('user', self::USER_ID)
            ->where('event', $event)
            ->count();
    }

    public function test_streak_grows_by_one_per_over_cap_sol(): void
    {
        $this->goOverCap();

        $this->tick(12600);
        $this->assertSame(1, $this->streak(), 'first over-cap Sol → streak 1');

        $this->tick(12601);
        $this->tick(12602);
        $this->assertSame(3, $this->streak(), 'streak counts consecutive over-cap Sols');
    }

    public function test_streak_stays_zero_within_cap(): void
    {
        $this->goWithinCap();

        $this->tick(12610);
        $this->tick(12611);

        $this->assertSame(0, $this->streak());
    }

    public function test_streak_resets_to_zero_once_back_within_cap(): void
    {
        $this->goOverCap();
        $this->tick(12620);
        $this->tick(12621);
        $this->assertSame(2, $this->streak());

        $this->goWithinCap();
        $this->tick(12622);

        $this->assertSame(0, $this->streak(), 'no carry-over: back within cap resets the streak immediately');
    }

    public function test_entering_over_capacity_is_logged_once_per_episode(): void
    {
        $this->goOverCap();
        $this->tick(12630);
        $this->tick(12631);
        $this->tick(12632);

        $this->assertSame(1, $this->logCount('colony.overcap_started'), 'only the transition into over-capacity is logged, not every Sol');

        $entry = DB::table('colony_log')->where('user', self::USER_ID)->where('event', 'colony.overcap_started')->first();
        $this->assertSame(12630, (int) $entry->tick);
        $params = json_decode($entry->parameters, true);
        $this->assertSame(self::COLONY_ID, $params['colony_id']);
        $this->assertGreaterThan(0, $params['deficit'], 'the log entry carries the colonist deficit');

        // New episode after recovering → logged again.
        $this->goWithinCap();
        $this->tick(12633);
        $this->goOverCap();
        $this->tick(12634);

        $this->assertSame(2, $this->logCount('colony.overcap_started'));
    }

    public function test_end_of_grace_period_is_logged_exactly_once(): void
    {
        $grace = (int) config('game.overcap.grace_sols');
        $this->goOverCap();

        for ($i = 0; $i < $grace; $i++) {
            $this->tick(12640 + $i);
        }
        $this->assertSame(0, $this->logCount('colony.overcap_trust_malus'), 'no malus entry during the grace period');

        $this->tick(12640 + $grace);       // streak = grace + 1 → malus starts
        $this->tick(12640 + $grace + 1);   // streak = grace + 2 → no new entry

        $this->assertSame(1, $this->logCount('colony.overcap_trust_malus'));
        $entry = DB::table('colony_log')->where('user', self::USER_ID)->where('event', 'colony.overcap_trust_malus')->first();
        $this->assertSame(12640 + $grace, (int) $entry->tick);
        $params = json_decode($entry->parameters, true);
        $this->assertSame((int) config('game.overcap.trust_base_malus'), $params['malus']);
    }

    /**
     * Placement in the tick: the streak must see level-downs from encounters in
     * the SAME Sol. A kritisch storm result levels housing down after the old
     * supply-cap step — with a stale stored cap the colony would only register
     * as over-cap one Sol late.
     */
    public function test_streak_sees_encounter_level_down_of_housing_in_the_same_sol(): void
    {
        config([
            'game.encounter.storm.base_chance' => 1.0,
            'game.encounter.storm.chance_cap' => 1.0,
            'game.encounter.cooldown_sols' => 0,
        ]);
        // Only CC (full SP → abgewehrt) and one housing row (low SP → kritisch) are storm-eligible.
        DB::table('colony_buildings')->where('colony_id', self::COLONY_ID)->whereIn('building_id', [31, 44, 46])->update(['level' => 0]);
        DB::table('colony_buildings')->where('colony_id', self::COLONY_ID)->where('building_id', 28)->where('instance_id', '!=', 1)->delete();
        DB::table('buildings')->whereIn('id', [25, 27, 28])->update(['decay_rate' => 0]);
        DB::table('colony_buildings')->where('colony_id', self::COLONY_ID)->where('building_id', 25)->update(['level' => 3, 'status_points' => 20]);
        DB::table('colony_buildings')->where('colony_id', self::COLONY_ID)->where('building_id', 28)->update(['level' => 2, 'status_points' => 5]);

        $this->tick(12650); // storm warning; cap still includes housing level 2
        $capBefore = (int) DB::table('user_resources')->where('user_id', self::USER_ID)->value('supply');
        // Used supply just below the pre-storm cap, above the post-storm cap (housing −1 level = −8 cap).
        DB::table('buildings')->where('id', self::HARVESTER)->update(['supply_cost' => $capBefore - 4]);

        $this->tick(12651); // storm resolves: housing kritisch → level 1

        $this->assertSame(1, (int) DB::table('colony_buildings')->where('colony_id', self::COLONY_ID)->where('building_id', 28)->value('level'), 'precondition: storm levelled housing down');
        $this->assertSame(1, $this->streak(), 'the housing lost in this Sol\'s storm must count toward over-capacity in the same Sol');
    }
}
