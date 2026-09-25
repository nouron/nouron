<?php

namespace Tests\Unit\Enums;

use App\Enums\BuildingId;
use PHPUnit\Framework\TestCase;

/**
 * Which buildings the colony build menu never offers — shared by
 * ColonyController::availableBuildings() and the techtree "errichten" link.
 */
class BuildingIdTest extends TestCase
{
    public function test_command_center_and_harvester_are_outside_the_build_menu(): void
    {
        $this->assertFalse(BuildingId::isBuildMenuBuilding(BuildingId::CommandCenter->value));
        $this->assertFalse(BuildingId::isBuildMenuBuilding(BuildingId::Harvester->value));
    }

    public function test_regular_buildings_are_build_menu_buildings(): void
    {
        $this->assertTrue(BuildingId::isBuildMenuBuilding(BuildingId::Housing->value));
        $this->assertTrue(BuildingId::isBuildMenuBuilding(BuildingId::Hangar->value));
        $this->assertTrue(BuildingId::isBuildMenuBuilding(46));
    }
}
