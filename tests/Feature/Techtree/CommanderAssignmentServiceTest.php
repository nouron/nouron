<?php

namespace Tests\Feature\Techtree;

/**
 * Service-level tests for PersonellService::assignCommander() and removeCommander().
 *
 * Covered scenarios:
 *   ASSIGN
 *     - assign_commander_sets_fleet_id_and_clears_colony_id
 *     - assign_commander_sets_is_commander_flag
 *     - assign_commander_fails_when_no_pilot_advisor_exists
 *     - assign_commander_fails_when_advisor_is_unavailable
 *     - assign_commander_fails_when_fleet_already_has_commander
 *
 *   REMOVE
 *     - remove_commander_restores_colony_id
 *     - remove_commander_clears_fleet_id_and_is_commander_flag
 *     - remove_commander_returns_false_when_no_commander_on_fleet
 */

use App\Models\Advisor;
use App\Models\Fleet;
use App\Services\Techtree\PersonellService;
use App\Services\TickService;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommanderAssignmentServiceTest extends TestCase
{
    use RefreshDatabase;

    // Fixture constants — Bart (user_id=3) owns colony 1 (Springfield)
    protected int $userId   = 3;
    protected int $colonyId = 1;

    protected PersonellService $service;
    protected int $pilotPersonellId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();

        $this->service         = $this->app->make(PersonellService::class);
        $this->pilotPersonellId = PersonellService::idFor('pilot');

        // Start clean: remove all advisors on colony 1 so tests are isolated
        Advisor::where('colony_id', $this->colonyId)->delete();
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function insertPilot(array $overrides = []): Advisor
    {
        return Advisor::create(array_merge([
            'user_id'               => $this->userId,
            'personell_id'          => $this->pilotPersonellId,
            'colony_id'             => $this->colonyId,
            'rank'                  => 1,
            'active_ticks'          => 0,
            'unavailable_until_tick' => null,
            'fleet_id'              => null,
            'is_commander'          => 0,
        ], $overrides));
    }

    private function createFleet(int $userId = null): Fleet
    {
        $fleet = new Fleet(['fleet' => 'Test Fleet', 'x' => 6828, 'y' => 3016]);
        $fleet->user_id = $userId ?? $this->userId;
        $fleet->save();
        return $fleet;
    }

    // ── assignCommander() ─────────────────────────────────────────────────────

    public function test_assign_commander_sets_fleet_id_and_clears_colony_id(): void
    {
        $pilot = $this->insertPilot();
        $fleet = $this->createFleet();

        $result = $this->service->assignCommander($this->colonyId, $fleet->id, $this->userId);

        $this->assertTrue($result);

        $pilot->refresh();
        $this->assertNull($pilot->colony_id, 'colony_id must be cleared after assignment');
        $this->assertEquals($fleet->id, $pilot->fleet_id, 'fleet_id must be set to the target fleet');
    }

    public function test_assign_commander_sets_is_commander_flag(): void
    {
        $pilot = $this->insertPilot();
        $fleet = $this->createFleet();

        $this->service->assignCommander($this->colonyId, $fleet->id, $this->userId);

        $pilot->refresh();
        $this->assertEquals(1, $pilot->is_commander, 'is_commander must be 1 after assignment');
    }

    public function test_assign_commander_fails_when_no_pilot_advisor_exists(): void
    {
        // No pilot advisor created — assignCommander must throw
        $fleet = $this->createFleet();

        $this->expectException(\RuntimeException::class);

        $this->service->assignCommander($this->colonyId, $fleet->id, $this->userId);
    }

    public function test_assign_commander_fails_when_advisor_is_unavailable(): void
    {
        // unavailable_until_tick set to a far-future tick — advisor is on cooldown
        $currentTick = $this->app->make(TickService::class)->getTickCount();
        $this->insertPilot(['unavailable_until_tick' => $currentTick + 9999]);

        $fleet = $this->createFleet();

        $this->expectException(\RuntimeException::class);

        $this->service->assignCommander($this->colonyId, $fleet->id, $this->userId);
    }

    public function test_assign_commander_fails_when_fleet_already_has_commander(): void
    {
        // Pre-assign a different advisor as commander on the fleet
        $existingCommander = Advisor::create([
            'user_id'      => $this->userId,
            'personell_id' => PersonellService::idFor('engineer'),
            'colony_id'    => null,
            'fleet_id'     => null, // will be set below
            'rank'         => 1,
            'active_ticks' => 0,
            'is_commander' => 0,
        ]);

        $fleet = $this->createFleet();

        // Set existing commander directly on the fleet
        $existingCommander->fleet_id     = $fleet->id;
        $existingCommander->is_commander = 1;
        $existingCommander->colony_id    = null;
        $existingCommander->save();

        // Now add a pilot to the colony
        $this->insertPilot();

        $this->expectException(\RuntimeException::class);

        $this->service->assignCommander($this->colonyId, $fleet->id, $this->userId);
    }

    // ── removeCommander() ─────────────────────────────────────────────────────

    public function test_remove_commander_restores_colony_id(): void
    {
        $fleet = $this->createFleet();
        $pilot = $this->insertPilot([
            'colony_id'    => null,
            'fleet_id'     => $fleet->id,
            'is_commander' => 1,
        ]);

        $result = $this->service->removeCommander($fleet->id, $this->colonyId, $this->userId);

        $this->assertTrue($result);

        $pilot->refresh();
        $this->assertEquals($this->colonyId, $pilot->colony_id, 'colony_id must be restored after remove');
    }

    public function test_remove_commander_clears_fleet_id_and_is_commander_flag(): void
    {
        $fleet = $this->createFleet();
        $pilot = $this->insertPilot([
            'colony_id'    => null,
            'fleet_id'     => $fleet->id,
            'is_commander' => 1,
        ]);

        $this->service->removeCommander($fleet->id, $this->colonyId, $this->userId);

        $pilot->refresh();
        $this->assertNull($pilot->fleet_id, 'fleet_id must be null after remove');
        $this->assertEquals(0, $pilot->is_commander, 'is_commander must be 0 after remove');
    }

    public function test_remove_commander_returns_false_when_no_commander_on_fleet(): void
    {
        $fleet = $this->createFleet();
        // No advisor with is_commander=1 on this fleet

        $result = $this->service->removeCommander($fleet->id, $this->colonyId, $this->userId);

        $this->assertFalse($result, 'removeCommander must return false when fleet has no commander (idempotent)');
    }
}
