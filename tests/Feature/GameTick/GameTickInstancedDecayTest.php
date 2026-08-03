<?php

namespace Tests\Feature\GameTick;

use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Regression test for the instance_id bug in GameTick::processBuildingDecay().
 *
 * The decay update WHERE clause historically filtered only on
 * (colony_id, building_id), which matches every instance row of an instanced
 * building (e.g. hangar, id=44). A single iteration of the decay loop would
 * therefore overwrite status_points on ALL instances at once, collapsing
 * independently-tracked instance state.
 *
 * Fixture: colony 1 (Springfield). Two hangar (building_id=44) instances are
 * seeded here with distinct starting status_points; each must decay
 * independently.
 */
class GameTickInstancedDecayTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
    }

    private function zeroAllSupplyCosts(): void
    {
        DB::table('buildings')->update(['supply_cost' => 0]);
        DB::table('researches')->update(['supply_cost' => 0]);
        DB::table('ships')->update(['supply_cost' => 0]);
    }

    private function getInstanceRow(int $colonyId, int $buildingId, int $instanceId): ?object
    {
        return DB::table('colony_buildings')
            ->where('colony_id', $colonyId)
            ->where('building_id', $buildingId)
            ->where('instance_id', $instanceId)
            ->first();
    }

    public function test_hangar_instances_decay_independently(): void
    {
        $this->zeroAllSupplyCosts();

        $hangarBuildingId = 44;
        $decayRate = (float) DB::table('buildings')->where('id', $hangarBuildingId)->value('decay_rate');
        $this->assertGreaterThan(0.0, $decayRate, 'Hangar must have a positive decay_rate for this test to be meaningful');

        // Seed two independent hangar instances with distinct starting status_points.
        DB::table('colony_buildings')->updateOrInsert(
            ['colony_id' => 1, 'building_id' => $hangarBuildingId, 'instance_id' => 1],
            ['level' => 1, 'status_points' => 10.0, 'ap_spend' => 0]
        );
        DB::table('colony_buildings')->updateOrInsert(
            ['colony_id' => 1, 'building_id' => $hangarBuildingId, 'instance_id' => 2],
            ['level' => 1, 'status_points' => 6.0, 'ap_spend' => 0]
        );

        Artisan::call('game:tick', ['--tick' => 11100]);

        $instance1 = $this->getInstanceRow(1, $hangarBuildingId, 1);
        $instance2 = $this->getInstanceRow(1, $hangarBuildingId, 2);

        $this->assertNotNull($instance1);
        $this->assertNotNull($instance2);

        // Each instance must decay by exactly one decay_rate step from its OWN
        // starting value — not be overwritten by the other instance's row.
        $this->assertEqualsWithDelta(10.0 - $decayRate, (float) $instance1->status_points, 0.001,
            'Hangar instance 1 must decay independently from its own starting status_points');
        $this->assertEqualsWithDelta(6.0 - $decayRate, (float) $instance2->status_points, 0.001,
            'Hangar instance 2 must decay independently from its own starting status_points');

        // The two instances must remain distinct after the tick (not collapsed
        // onto the same value by a shared, instance_id-blind WHERE clause).
        $this->assertNotEquals(
            (float) $instance1->status_points,
            (float) $instance2->status_points,
            'Hangar instances must retain independent status_points after decay'
        );
    }
}
