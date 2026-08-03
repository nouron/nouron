<?php

namespace Tests\Feature;

/**
 * BuildingMaxInstancesTest — schema coverage for the `buildings.max_instances` split.
 *
 * Context: `max_level` used to be overloaded for instanced buildings (harvester,
 * housingComplex, hangar) — it meant "max level" for regular buildings but was also
 * read as an instance-count cap elsewhere. This migration adds a dedicated
 * `max_instances` column (nullable, NULL = unbounded) so the two axes can be
 * configured independently (GDD §4c, Owner-Entscheidung 2026-08-03).
 *
 * Covered:
 *   - `buildings.max_instances` column exists after migrate:fresh + seed
 *   - harvester (27): max_instances=2, max_level=1 (no level-up, hard-capped at 2 instances)
 *   - housingComplex (28): max_instances=6 carried over from the previous max_level
 *     cap; max_level stays 6 (level is a live, independently-tracked axis — see
 *     ResourcesService::getSupplyBreakdown() which sums per-instance `level` for the
 *     supply contribution, so nulling it out would silently uncap housing supply)
 *   - Building::create()/update() can mass-assign max_instances (fillable)
 *   - ColonyBuilding::create() can mass-assign instance_id without MassAssignmentException,
 *     and a second instance of the same building does not collide with the
 *     (colony_id, building_id, instance_id) unique key
 */

use App\Models\Building;
use App\Models\ColonyBuilding;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class BuildingMaxInstancesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(TestSeeder::class);
    }

    public function test_max_instances_column_exists_on_buildings_table(): void
    {
        $this->assertTrue(
            Schema::hasColumn('buildings', 'max_instances'),
            'buildings.max_instances column is missing'
        );
    }

    public function test_harvester_has_max_instances_two_and_max_level_one(): void
    {
        $harvester = DB::table('buildings')->where('id', 27)->first();

        $this->assertNotNull($harvester);
        $this->assertSame(2, (int) $harvester->max_instances);
        $this->assertSame(1, (int) $harvester->max_level);
    }

    public function test_housing_complex_has_max_instances_six_and_keeps_max_level_six(): void
    {
        $housing = DB::table('buildings')->where('id', 28)->first();

        $this->assertNotNull($housing);
        $this->assertSame(6, (int) $housing->max_instances);
        $this->assertSame(6, (int) $housing->max_level);
    }

    public function test_building_model_can_mass_assign_max_instances(): void
    {
        $building = Building::create([
            'id' => 9001,
            'purpose' => 'industry',
            'name' => 'building_test_fixture',
            'required_building_id' => null,
            'required_building_level' => null,
            'prime_colony_only' => 0,
            'row' => 0,
            'column' => 0,
            'max_level' => 1,
            'max_instances' => 3,
            'ap_for_levelup' => 10,
            'max_status_points' => 20,
        ]);

        $this->assertSame(3, $building->max_instances);

        $building->update(['max_instances' => 4]);
        $this->assertSame(4, $building->fresh()->max_instances);
    }

    public function test_colony_building_model_can_mass_assign_instance_id(): void
    {
        // colony_id=1 + building_id=28 (housingComplex) already has instance_id=1
        // seeded by TestSeeder (default) — a second instance must not collide with
        // the (colony_id, building_id, instance_id) unique key.
        $colonyBuilding = ColonyBuilding::create([
            'colony_id' => 1,
            'building_id' => 28,
            'instance_id' => 2,
            'level' => 1,
            'status_points' => 20,
            'ap_spend' => 0,
        ]);

        $this->assertSame(2, $colonyBuilding->instance_id);

        $this->assertDatabaseHas('colony_buildings', [
            'colony_id' => 1,
            'building_id' => 28,
            'instance_id' => 2,
        ]);
    }
}
