<?php

namespace Tests\Feature\Colony;

use App\Enums\BuildingId;
use App\Services\BuildingCostService;
use Tests\TestCase;

/**
 * BuildingCostService — single source for erect / level-up / placement costs of
 * colony buildings (T9, Owner rule 2026-09-25: placing pays the full Regolith up
 * to level 1).
 */
class BuildingCostServiceTest extends TestCase
{
    private function service(): BuildingCostService
    {
        return $this->app->make(BuildingCostService::class);
    }

    public function test_placement_cost_adds_first_level_regolith_to_erect_cost(): void
    {
        $this->assertSame([3 => 85, 4 => 25], $this->service()->placementCost(46));   // infirmary 60 Rg + 25 Wk
        $this->assertSame([3 => 120], $this->service()->placementCost(31));           // sciencelab 95 Rg
    }

    public function test_harvester_placement_is_free(): void
    {
        $this->assertSame([], $this->service()->placementCost(BuildingId::Harvester->value));
    }

    public function test_first_level_regolith_is_the_flat_level_up_cost(): void
    {
        $this->assertSame(25, $this->service()->firstLevelRegolith(46));
        $this->assertSame(0, $this->service()->firstLevelRegolith(BuildingId::Harvester->value));
    }

    public function test_level_up_regolith_due_is_zero_for_a_placed_level_zero_site(): void
    {
        $site = (object) ['building_id' => 46, 'level' => 0, 'tile_x' => 1];

        $this->assertTrue(BuildingCostService::firstLevelPrepaid($site));
        $this->assertSame(0, $this->service()->levelupRegolithDue($site));
    }

    public function test_level_up_regolith_due_is_charged_for_later_levels(): void
    {
        $built = (object) ['building_id' => 46, 'level' => 1, 'tile_x' => 1];
        $cc = (object) ['building_id' => BuildingId::CommandCenter->value, 'level' => 2, 'tile_x' => null];

        $this->assertFalse(BuildingCostService::firstLevelPrepaid($built));
        $this->assertSame(25, $this->service()->levelupRegolithDue($built));
        $this->assertSame(90, $this->service()->levelupRegolithDue($cc));   // 3 × 30
    }

    public function test_flat_level_up_regolith_is_read_from_config(): void
    {
        config(['game.build.levelup_regolith_flat' => 30]);

        $this->assertSame(30, $this->service()->firstLevelRegolith(46));
        $this->assertSame([3 => 90, 4 => 25], $this->service()->placementCost(46));
        $this->assertSame(30, $this->service()->levelupRegolithDue((object) ['building_id' => 46, 'level' => 1, 'tile_x' => 1]));
    }

    public function test_config_ships_the_flat_level_up_regolith(): void
    {
        $this->assertSame(25, config('game.build.levelup_regolith_flat'));
    }
}
