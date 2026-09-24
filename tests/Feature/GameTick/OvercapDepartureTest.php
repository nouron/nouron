<?php

namespace Tests\Feature\GameTick;

use App\Services\ResourcesService;
use App\Services\TrustService;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * A14 "Unterbesetzung" (GDD §6 "Überkapazität — Konsequenzen"): homeless
 * colonists stay for a short deadline (overcap.departure_after_sols Sols with a
 * trust penalty from the first Sol), then all of them leave at once:
 * glx_colonies.overcap_departed += homeless, streak → 0, trust event
 * colonists_left exactly once. Buildings keep their levels. Once housing is free
 * again, departed colonists return automatically (partially or fully).
 *
 * Timing (GDD table: trust_cap is reached on the last Sol before departure):
 * Sols 1..N carry the streak 1..N and its penalty; the tick after that — the
 * stored streak has reached N — is the departure.
 *
 * Setup: supply costs zeroed, decay and storms disabled so the cap only changes
 * when a test changes housing. A settle tick stores the cap; the Harvester
 * (27, level 1) then gets supply_cost = cap + n → exactly n homeless colonists.
 * Housing instance 1 (28) is used to add or remove 8 cap per level.
 *
 * Fixture colony 1 (user 3). Uses tick numbers 12800–12899.
 */
class OvercapDepartureTest extends TestCase
{
    use RefreshDatabase;

    private const COLONY_ID = 1;

    private const USER_ID = 3;

    private const HARVESTER = 27;

    private const HOUSING = 28;

    private const INFIRMARY = 46;

    private const DEADLINE = 3;

    private int $cap = 0;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();

        config([
            'game.overcap.departure_after_sols' => self::DEADLINE,
            'game.overcap.trust_base_malus' => 2,
            'game.overcap.trust_step' => 1,
            'game.overcap.trust_cap' => 4,
            'game.encounter.storm.base_chance' => 0.0,
            'game.encounter.storm.chance_cap' => 0.0,
            'buildings.housingComplex.supply_cap' => 8,
        ]);

        DB::table('buildings')->update(['supply_cost' => 0, 'decay_rate' => 0]);
        DB::table('researches')->update(['supply_cost' => 0]);
        DB::table('ships')->update(['supply_cost' => 0]);

        $this->tick(12800); // settle: stores the fixture cap
        $this->cap = (int) DB::table('user_resources')->where('user_id', self::USER_ID)->value('supply');
        DB::table('colony_log')->where('user', self::USER_ID)->delete();
        DB::table('trust_events')->where('colony_id', self::COLONY_ID)->delete();
    }

    // ── helpers ────────────────────────────────────────────────────────────

    private function tick(int $tick): void
    {
        Artisan::call('game:tick', ['--tick' => $tick]);
    }

    private function makeHomeless(int $n): void
    {
        DB::table('buildings')->where('id', self::HARVESTER)->update(['supply_cost' => $this->cap + $n]);
    }

    private function changeHousing(int $delta): void
    {
        DB::table('colony_buildings')
            ->where('colony_id', self::COLONY_ID)->where('building_id', self::HOUSING)->where('instance_id', 1)
            ->increment('level', $delta);
    }

    private function colony(): object
    {
        return DB::table('glx_colonies')->where('id', self::COLONY_ID)->first();
    }

    private function leftEvents(): int
    {
        return DB::table('trust_events')
            ->where('colony_id', self::COLONY_ID)
            ->where('event_type', 'colonists_left')
            ->count();
    }

    /** @return list<array<string, mixed>> */
    private function logParams(string $event): array
    {
        return DB::table('colony_log')
            ->where('user', self::USER_ID)
            ->where('event', $event)
            ->orderBy('tick')
            ->pluck('parameters')
            ->map(fn ($p) => json_decode((string) $p, true))
            ->all();
    }

    /** @return array<string, int> */
    private function levels(): array
    {
        return DB::table('colony_buildings')
            ->where('colony_id', self::COLONY_ID)
            ->get()
            ->mapWithKeys(fn ($r) => [$r->building_id.'#'.$r->instance_id => (int) $r->level])
            ->all();
    }

    private function homeless(): int
    {
        return $this->app->make(ResourcesService::class)->colonistStatus(self::COLONY_ID)['homeless'];
    }

    // ── tests ──────────────────────────────────────────────────────────────

    public function test_trust_penalty_applies_from_the_first_sol_with_homeless_colonists(): void
    {
        $this->makeHomeless(7);

        $this->tick(12801);

        $this->assertSame(1, (int) $this->colony()->overcap_streak);
        $this->assertSame(-2, $this->app->make(TrustService::class)->overcapPenalty(self::COLONY_ID));
    }

    public function test_homeless_colonists_depart_after_the_deadline(): void
    {
        $this->makeHomeless(7);
        $levelsBefore = $this->levels();

        for ($i = 1; $i <= self::DEADLINE; $i++) {
            $this->tick(12810 + $i);
            $this->assertSame($i, (int) $this->colony()->overcap_streak, "Sol {$i} of the deadline");
            $this->assertSame(0, (int) $this->colony()->overcap_departed, 'nobody leaves during the deadline');
        }
        $this->assertSame(0, $this->leftEvents());

        $this->tick(12810 + self::DEADLINE + 1);

        $colony = $this->colony();
        $this->assertSame(7, (int) $colony->overcap_departed, 'all homeless colonists leave at once');
        $this->assertSame(0, (int) $colony->overcap_streak, 'departure ends the streak');
        $this->assertSame(0, $this->homeless());
        $this->assertSame(1, $this->leftEvents(), 'colonists_left fires exactly once per departure');
        $this->assertSame($levelsBefore, $this->levels(), 'departure costs no building level');

        $log = $this->logParams('colony.colonists_left');
        $this->assertCount(1, $log);
        $this->assertSame(7, $log[0]['count']);

        // Next Sol: understaffed but nobody homeless → nothing further happens.
        $this->tick(12810 + self::DEADLINE + 2);
        $this->assertSame(7, (int) $this->colony()->overcap_departed);
        $this->assertSame(0, (int) $this->colony()->overcap_streak);
        $this->assertSame(1, $this->leftEvents());
    }

    public function test_restoring_housing_within_the_deadline_prevents_departure(): void
    {
        $this->makeHomeless(7);
        $this->tick(12821);
        $this->tick(12822);

        $this->changeHousing(+1); // +8 cap ≥ 7 homeless
        $this->tick(12823);
        $this->tick(12824);
        $this->tick(12825);

        $this->assertSame(0, (int) $this->colony()->overcap_departed);
        $this->assertSame(0, (int) $this->colony()->overcap_streak);
        $this->assertSame(0, $this->leftEvents());
    }

    public function test_departed_colonists_return_partially_then_fully_with_new_housing(): void
    {
        $this->makeHomeless(12);
        for ($i = 1; $i <= self::DEADLINE + 1; $i++) {
            $this->tick(12830 + $i);
        }
        $this->assertSame(12, (int) $this->colony()->overcap_departed);

        $this->changeHousing(+1); // +8 room → 8 of 12 return
        $this->tick(12840);

        $this->assertSame(4, (int) $this->colony()->overcap_departed, 'partial return');
        $returned = $this->logParams('colony.colonists_returned');
        $this->assertCount(1, $returned);
        $this->assertSame(8, $returned[0]['count']);

        $this->changeHousing(+1); // room for the remaining 4
        $this->tick(12841);

        $this->assertSame(0, (int) $this->colony()->overcap_departed, 'full return, never more than departed');
        $returned = $this->logParams('colony.colonists_returned');
        $this->assertCount(2, $returned);
        $this->assertSame(4, $returned[1]['count']);
        $this->assertSame(1.0, $this->app->make(ResourcesService::class)->staffingShare(self::COLONY_ID));
    }

    public function test_a_second_departure_adds_to_the_stored_count(): void
    {
        $this->makeHomeless(7);
        for ($i = 1; $i <= self::DEADLINE + 1; $i++) {
            $this->tick(12850 + $i);
        }
        $this->assertSame(7, (int) $this->colony()->overcap_departed);

        $this->changeHousing(-1); // −8 cap → 8 new homeless, own deadline
        for ($i = 1; $i <= self::DEADLINE + 1; $i++) {
            $this->tick(12860 + $i);
        }

        $this->assertSame(15, (int) $this->colony()->overcap_departed);
        $this->assertSame(2, $this->leftEvents());
        $log = $this->logParams('colony.colonists_left');
        $this->assertSame(8, $log[1]['count']);
    }

    /** 7 colonists departed; the Infirmary (46, fixture level 3) holds 6 of the workplaces. */
    private function departSevenWithInfirmaryWorkplaces(int $tickBase): void
    {
        // 2 workplaces per Infirmary level = 6.
        DB::table('buildings')->where('id', self::INFIRMARY)->update(['supply_cost' => 2]);
        DB::table('buildings')->where('id', self::HARVESTER)->update(['supply_cost' => $this->cap + 7 - 6]);
        for ($i = 1; $i <= self::DEADLINE + 1; $i++) {
            $this->tick($tickBase + $i);
        }
        $this->assertSame(7, (int) $this->colony()->overcap_departed);
    }

    private function decayInfirmaryOneLevelNextTick(): void
    {
        DB::table('buildings')->where('id', self::INFIRMARY)->update(['decay_rate' => 5]);
        DB::table('colony_buildings')->where('colony_id', self::COLONY_ID)->where('building_id', self::INFIRMARY)
            ->update(['status_points' => 1]);
    }

    /**
     * Workplace loss (a production building loses a level through decay or a storm)
     * removes unfilled workplaces silently — nobody came back, so no
     * colonists_returned entry (A14 fix 2026-09-24).
     */
    public function test_lost_workplaces_shrink_the_departed_count_silently(): void
    {
        $this->departSevenWithInfirmaryWorkplaces(12870);
        $this->decayInfirmaryOneLevelNextTick();

        $this->tick(12880);

        $this->assertSame(2, (int) DB::table('colony_buildings')->where('colony_id', self::COLONY_ID)
            ->where('building_id', self::INFIRMARY)->value('level'), 'the infirmary lost a level');
        $this->assertSame(5, (int) $this->colony()->overcap_departed, 'two unfilled workplaces are gone');
        $this->assertSame([], $this->logParams('colony.colonists_returned'), 'nobody came back');
        $this->assertSame(0, $this->homeless());
    }

    public function test_only_colonists_moving_back_in_are_logged_as_returned(): void
    {
        $this->departSevenWithInfirmaryWorkplaces(12885);
        $this->decayInfirmaryOneLevelNextTick();
        $this->changeHousing(+1); // +8 room in the same Sol

        $this->tick(12895);

        $this->assertSame(0, (int) $this->colony()->overcap_departed);
        $returned = $this->logParams('colony.colonists_returned');
        $this->assertCount(1, $returned);
        $this->assertSame(5, $returned[0]['count'], '2 of the 7 workplaces vanished, 5 colonists moved back in');
    }
}
