<?php

namespace Tests\Feature\GameTick;

/**
 * GameTick — Deva's "Drill-Reaktionspuffer" (Vier-Ausgänge-Pool Mechanical A,
 * GDD §12 "Deva & Lenn — taktische Information", A42).
 *
 * An active buffer (colony_information_pool_state.active_drill_buffer) halves
 * the consequence of the NEXT triggered Instabilität/Seuche encounter and is
 * consumed (flipped back to false) the moment that happens.
 *
 * Covered scenarios:
 *  - test_active_buffer_halves_instability_outage_and_is_consumed
 *  - test_instability_without_active_buffer_uses_full_outage
 *  - test_active_buffer_halves_plague_ap_reduction_and_shortens_debuff_and_is_consumed
 *  - test_plague_without_active_buffer_uses_full_debuff
 */

use App\Services\AdvisorService;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class GameTickDrillBufferTest extends TestCase
{
    use RefreshDatabase;

    private const COLONY_ID = 1; // Springfield

    private const HARVESTER_ID = 27;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
    }

    private function setActiveDrillBuffer(bool $active): void
    {
        DB::table('colony_information_pool_state')->updateOrInsert(
            ['colony_id' => self::COLONY_ID],
            ['active_drill_buffer' => $active]
        );
    }

    public function test_active_buffer_halves_instability_outage_and_is_consumed(): void
    {
        config([
            'game.encounter.instability.chance_per_sol_since_relocation' => 1.0,
            'game.encounter.instability.chance_cap' => 1.0,
            'game.encounter.cooldown_sols' => 0,
        ]);
        DB::table('colony_buildings')
            ->where('colony_id', self::COLONY_ID)->where('building_id', self::HARVESTER_ID)
            ->update(['placed_at_tick' => 11000]);
        $this->setActiveDrillBuffer(true);

        $this->artisan('game:tick', ['--tick' => 11800])->assertExitCode(0);

        $harvester = DB::table('colony_buildings')
            ->where('colony_id', self::COLONY_ID)->where('building_id', self::HARVESTER_ID)
            ->first();
        $fullOutage = (int) config('game.encounter.instability.outage_sols');
        $expectedOutage = max(1, (int) ceil($fullOutage * 0.5));
        $this->assertEquals(11800 + $expectedOutage, (int) $harvester->instability_outage_until_tick);

        $state = DB::table('colony_information_pool_state')->where('colony_id', self::COLONY_ID)->first();
        $this->assertFalse((bool) $state->active_drill_buffer, 'the buffer must be consumed once it applied');
    }

    public function test_instability_without_active_buffer_uses_full_outage(): void
    {
        config([
            'game.encounter.instability.chance_per_sol_since_relocation' => 1.0,
            'game.encounter.instability.chance_cap' => 1.0,
            'game.encounter.cooldown_sols' => 0,
        ]);
        DB::table('colony_buildings')
            ->where('colony_id', self::COLONY_ID)->where('building_id', self::HARVESTER_ID)
            ->update(['placed_at_tick' => 11000]);

        $this->artisan('game:tick', ['--tick' => 11801])->assertExitCode(0);

        $harvester = DB::table('colony_buildings')
            ->where('colony_id', self::COLONY_ID)->where('building_id', self::HARVESTER_ID)
            ->first();
        $fullOutage = (int) config('game.encounter.instability.outage_sols');
        $this->assertEquals(11801 + $fullOutage, (int) $harvester->instability_outage_until_tick);
    }

    public function test_active_buffer_halves_plague_ap_reduction_and_shortens_debuff_and_is_consumed(): void
    {
        config([
            'game.encounter.storm.base_chance' => 0.0,
            'game.encounter.storm.chance_per_building' => 0.0,
            'game.encounter.instability.chance_per_sol_since_relocation' => 0.0,
            'game.encounter.plague.chance_per_sol_when_emergent' => 1.0,
            'game.encounter.cooldown_sols' => 0,
            'game.encounter.phase1_ramp_sols' => 1,
        ]);
        DB::table('glx_colonies')->where('id', self::COLONY_ID)->update(['hunger_streak' => 3]);
        DB::table('colony_resources')->where('colony_id', self::COLONY_ID)->where('resource_id', 5)->update(['amount' => 0]);
        DB::table('colony_buildings')->where('colony_id', self::COLONY_ID)->where('building_id', (int) config('buildings.infirmary.id', 46))->update(['level' => 0]);
        $this->setActiveDrillBuffer(true);

        $tick = 11900;
        $this->artisan('game:tick', ['--tick' => $tick])->assertExitCode(0);

        $colony = DB::table('glx_colonies')->where('id', self::COLONY_ID)->first();
        $this->assertNotNull($colony->plague_until_tick);

        $fullDebuff = (int) config('game.encounter.plague.debuff_sols');
        $fullReduction = (float) config('game.encounter.plague.ap_reduction_pct');
        $this->assertSame(max(1, $fullDebuff - 1), (int) $colony->plague_until_tick - $tick);
        $this->assertEqualsWithDelta($fullReduction * 0.5, (float) $colony->plague_ap_reduction_pct, 0.0001);

        $state = DB::table('colony_information_pool_state')->where('colony_id', self::COLONY_ID)->first();
        $this->assertFalse((bool) $state->active_drill_buffer, 'the buffer must be consumed once it applied');

        // AdvisorService must actually use the halved value, not just store it.
        $advisorService = $this->app->make(AdvisorService::class);
        $breakdown = $advisorService->getApBreakdown(self::COLONY_ID);
        $expectedMultiplier = 1 - $fullReduction * 0.5;
        $this->assertEqualsWithDelta($expectedMultiplier, $breakdown['plague_multiplier'], 0.0001);
    }

    public function test_plague_without_active_buffer_uses_full_debuff(): void
    {
        config([
            'game.encounter.storm.base_chance' => 0.0,
            'game.encounter.storm.chance_per_building' => 0.0,
            'game.encounter.instability.chance_per_sol_since_relocation' => 0.0,
            'game.encounter.plague.chance_per_sol_when_emergent' => 1.0,
            'game.encounter.cooldown_sols' => 0,
            'game.encounter.phase1_ramp_sols' => 1,
        ]);
        DB::table('glx_colonies')->where('id', self::COLONY_ID)->update(['hunger_streak' => 3]);
        DB::table('colony_resources')->where('colony_id', self::COLONY_ID)->where('resource_id', 5)->update(['amount' => 0]);
        DB::table('colony_buildings')->where('colony_id', self::COLONY_ID)->where('building_id', (int) config('buildings.infirmary.id', 46))->update(['level' => 0]);

        $tick = 11901;
        $this->artisan('game:tick', ['--tick' => $tick])->assertExitCode(0);

        $colony = DB::table('glx_colonies')->where('id', self::COLONY_ID)->first();
        $this->assertNotNull($colony->plague_until_tick);

        $fullDebuff = (int) config('game.encounter.plague.debuff_sols');
        $fullReduction = (float) config('game.encounter.plague.ap_reduction_pct');
        $this->assertSame($fullDebuff, (int) $colony->plague_until_tick - $tick);
        $this->assertEqualsWithDelta($fullReduction, (float) $colony->plague_ap_reduction_pct, 0.0001);
    }
}
