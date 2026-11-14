<?php

namespace Tests\Feature\ApScale;

use App\Models\Advisor;
use App\Services\AdvisorService;
use App\Services\BuildingApCapService;
use App\Services\PathDepthService;
use App\Services\Techtree\ResearchService;
use App\Services\TickService;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * AP scale spec 2026-11-13 §11.1 option B: every specialist advisor raises the per-Sol cap
 * of its own field by game.advisor.cap_bonus_per_rank — engineer -> building, scientist ->
 * lab (knowledge), pilot -> training. An unavailable advisor adds nothing.
 */
class SpecialistCapBonusTest extends TestCase
{
    use RefreshDatabase;

    private const COLONY_ID = 1;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
        $this->app->instance(TickService::class, new TickService(10));
        Advisor::where('colony_id', self::COLONY_ID)->delete();
        config([
            'game.build.ap_cap_per_sol.base' => 1,
            'game.knowledge_ap_cap_per_sol.base' => 1,
            'path_depth.training_ap_cap_per_sol.base' => 1,
            'game.advisor.cap_bonus_per_rank' => [1 => 1, 2 => 2, 3 => 2],
        ]);
    }

    private function hire(string $key, int $rank, ?int $unavailableUntil = null): void
    {
        Advisor::create([
            'user_id' => 3,
            'personell_id' => AdvisorService::idFor($key),
            'colony_id' => self::COLONY_ID,
            'rank' => $rank,
            'active_ticks' => 0,
            'unavailable_until_tick' => $unavailableUntil,
        ]);
    }

    public function test_building_cap_is_base_without_engineer(): void
    {
        $this->assertSame(1, app(BuildingApCapService::class)->capFor(self::COLONY_ID));
    }

    public function test_building_cap_adds_engineer_rank_bonus(): void
    {
        $this->hire('engineer', 2);

        $this->assertSame(3, app(BuildingApCapService::class)->capFor(self::COLONY_ID));
    }

    public function test_knowledge_cap_adds_scientist_rank_bonus(): void
    {
        $service = app(ResearchService::class);
        $this->assertSame(1, $service->apCap(self::COLONY_ID));

        $this->hire('scientist', 1);

        $this->assertSame(2, $service->apCap(self::COLONY_ID));
    }

    public function test_training_cap_adds_pilot_rank_bonus(): void
    {
        $service = app(PathDepthService::class);
        $this->assertSame(1, $service->trainingCap(self::COLONY_ID));

        $this->hire('pilot', 3);

        $this->assertSame(3, $service->trainingCap(self::COLONY_ID));
    }

    public function test_unavailable_specialist_adds_nothing(): void
    {
        $this->hire('scientist', 2, unavailableUntil: 12);

        $this->assertSame(1, app(ResearchService::class)->apCap(self::COLONY_ID));
    }

    public function test_other_advisor_does_not_raise_a_foreign_cap(): void
    {
        $this->hire('engineer', 3);

        $this->assertSame(1, app(ResearchService::class)->apCap(self::COLONY_ID));
        $this->assertSame(1, app(PathDepthService::class)->trainingCap(self::COLONY_ID));
    }

    public function test_null_base_keeps_rule_off(): void
    {
        config(['game.knowledge_ap_cap_per_sol.base' => null]);
        $this->hire('scientist', 2);

        $this->assertNull(app(ResearchService::class)->apCap(self::COLONY_ID));
    }
}
