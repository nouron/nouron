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
}
