<?php

namespace Tests\Feature\Hangar;

use App\Services\HangarService;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class HangarServiceTest extends TestCase
{
    use RefreshDatabase;

    private const COLONY_ID = 1;

    private const USER_ID = 3;

    private const HANGAR_INSTANCE = 1;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
    }

    public function test_success_chance_uses_base_chance_when_no_bonuses_apply(): void
    {
        $mission = config('missions.catalog.mission_courier_run'); // ungegatet
        $service = $this->app->make(HangarService::class);

        $chance = $service->successChanceFor(self::COLONY_ID, $mission, 'leicht');

        $this->assertSame(0.85, $chance, 'no pilot, no knowledge gate => base_chance only');
    }

    public function test_success_chance_adds_pilot_rank_bonus(): void
    {
        // Real advisors table columns (see data/sql/testdata.sqlite.sql): user_id,
        // colony_id, personell_id, rank, active_ticks — no hired_tick column.
        DB::table('advisors')->insert([
            'user_id' => self::USER_ID,
            'colony_id' => self::COLONY_ID,
            'personell_id' => config('advisors.pilot.id'),
            'rank' => 2,
            'active_ticks' => 0,
        ]);
        $mission = config('missions.catalog.mission_courier_run');
        $service = $this->app->make(HangarService::class);

        $chance = $service->successChanceFor(self::COLONY_ID, $mission, 'leicht');

        $this->assertEqualsWithDelta(0.85 + 2 * 0.05, $chance, 0.0001, 'rank 2 => +0.10 on top of base_chance');
    }

    public function test_success_chance_adds_knowledge_bonus_above_gate(): void
    {
        // mission_prospecting_flight requires geology Lv1 — colony sits at Lv3, 2 levels above gate.
        DB::table('colony_researches')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'research_id' => config('knowledge.geology.id')],
            ['level' => 3, 'ap_spend' => 0]
        );
        $mission = config('missions.catalog.mission_prospecting_flight');
        $service = $this->app->make(HangarService::class);

        $chance = $service->successChanceFor(self::COLONY_ID, $mission, 'normal');

        $this->assertEqualsWithDelta(0.70 + 2 * 0.03, $chance, 0.0001, '2 levels above the Lv1 gate => +0.06');
    }

    public function test_success_chance_is_capped(): void
    {
        DB::table('advisors')->insert([
            'user_id' => self::USER_ID,
            'colony_id' => self::COLONY_ID,
            'personell_id' => config('advisors.pilot.id'),
            'rank' => 3,
            'active_ticks' => 0,
        ]);
        DB::table('colony_researches')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'research_id' => config('knowledge.geology.id')],
            ['level' => 5, 'ap_spend' => 0]
        );
        $mission = config('missions.catalog.mission_prospecting_flight');
        $service = $this->app->make(HangarService::class);

        // leicht base_chance 0.85 + rank3*0.05=0.15 + 4 levels above gate*0.03=0.12 = 1.12 uncapped,
        // must clamp to chance_cap 0.95. ('schwer' base 0.60 would only reach 0.87 here — not high
        // enough to ever hit the cap with realistic level 5 knowledge, so this must use 'leicht'.)
        $chance = $service->successChanceFor(self::COLONY_ID, $mission, 'leicht');

        $this->assertSame(0.95, $chance, 'must clamp at chance_cap even with max pilot rank + knowledge overshoot');
    }

    public function test_dispatch_rejects_a_difficulty_not_offered_by_the_mission(): void
    {
        $service = $this->app->make(HangarService::class);
        // TestSeeder docks a corvette (ship_id 37) at hangar instance 1 (colony 1's
        // seeded drone sits dispatched there instead — see HangarMissionResolutionTest
        // fixture comment), so mission_escort_convoy (ships => ['corvette'], no
        // knowledge/target gate) is the compatible catalog mission here, not
        // mission_courier_run (drone-only). Its difficulties are ['normal', 'schwer']
        // (config/missions.php) — 'leicht' must be rejected.
        $this->expectException(\RuntimeException::class);

        $service->dispatchShip(self::COLONY_ID, self::HANGAR_INSTANCE, 'mission_escort_convoy', null, 'leicht');
    }

    public function test_dispatch_persists_the_chosen_difficulty(): void
    {
        $service = $this->app->make(HangarService::class);

        $service->dispatchShip(self::COLONY_ID, self::HANGAR_INSTANCE, 'mission_escort_convoy', null, 'schwer');

        // TestSeeder already seeds a (recalled/inactive-irrelevant) mission row for
        // colony 1 / instance 1 (mission_recon_flight, default difficulty 'normal') —
        // scope to the freshly dispatched row via destination + state to avoid picking
        // that stale fixture row up instead.
        $this->assertSame('schwer', DB::table('colony_hangar_missions')
            ->where('colony_id', self::COLONY_ID)->where('instance_id', self::HANGAR_INSTANCE)
            ->where('destination', 'mission_escort_convoy')->where('state', 'active')
            ->value('difficulty'));
    }

    public function test_mission_catalog_includes_difficulty_options_with_chance_and_multiplier(): void
    {
        $service = $this->app->make(HangarService::class);

        $entries = $service->getMissionCatalogFor(self::COLONY_ID);
        $courierRun = collect($entries)->firstWhere('key', 'mission_courier_run');

        $this->assertNotNull($courierRun['difficulty_options']);
        $this->assertCount(2, $courierRun['difficulty_options'], 'mission_courier_run offers leicht + normal');
        $leicht = collect($courierRun['difficulty_options'])->firstWhere('key', 'leicht');
        $this->assertSame('Leicht', $leicht['label']);
        $this->assertSame(85, $leicht['chance_pct'], 'base_chance 0.85, no bonuses => 85%');
        $this->assertSame(0.7, $leicht['reward_multiplier']);
    }
}
