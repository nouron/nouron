<?php

namespace Tests\Feature\Techtree;

use App\Models\Advisor;
use App\Services\Techtree\PersonellService;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PersonellServiceTest extends TestCase
{
    use RefreshDatabase;

    protected PersonellService $service;
    protected int $userId   = 3;   // Bart in test data
    protected int $colonyId = 1;
    protected int $fleetId  = 10;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
        $this->service = $this->app->make(PersonellService::class);

        // Clear existing seeded advisors for our test colony/fleet so counts are predictable
        Advisor::where('colony_id', $this->colonyId)->delete();
        Advisor::where('fleet_id', $this->fleetId)->delete();

        // 2 engineers: rank 2 (7 AP) + rank 1 (4 AP) = 11 construction AP
        Advisor::create([
            'user_id' => $this->userId, 'personell_id' => PersonellService::PERSONELL_ID_ENGINEER,
            'colony_id' => $this->colonyId, 'rank' => 2, 'active_ticks' => 5,
        ]);
        Advisor::create([
            'user_id' => $this->userId, 'personell_id' => PersonellService::PERSONELL_ID_ENGINEER,
            'colony_id' => $this->colonyId, 'rank' => 1, 'active_ticks' => 2,
        ]);
        // 1 scientist: rank 1 = 4 research AP
        Advisor::create([
            'user_id' => $this->userId, 'personell_id' => PersonellService::PERSONELL_ID_SCIENTIST,
            'colony_id' => $this->colonyId, 'rank' => 1, 'active_ticks' => 0,
        ]);
        // 1 Kommandant on fleet: rank 1 = 4 navigation AP
        Advisor::create([
            'user_id' => $this->userId, 'personell_id' => PersonellService::PERSONELL_ID_PILOT,
            'fleet_id' => $this->fleetId, 'is_commander' => true, 'rank' => 1, 'active_ticks' => 0,
        ]);
    }

    public function testGetTotalActionPoints(): void
    {
        // 2 engineers: rank2(7) + rank1(4) = 11
        $this->assertEquals(11, $this->service->getTotalActionPoints('construction', $this->colonyId));
        // 1 scientist rank1 = 4
        $this->assertEquals(4, $this->service->getTotalActionPoints('research', $this->colonyId));
        // 1 commander rank1 on fleet = 4
        $this->assertEquals(4, $this->service->getTotalActionPoints('navigation', $this->fleetId));
        // unknown = 0
        $this->assertEquals(0, $this->service->getTotalActionPoints('unknown', $this->colonyId));
    }

    public function testGetAvailableActionPoints(): void
    {
        $this->assertEquals(11, $this->service->getAvailableActionPoints('construction', $this->colonyId));
        $this->assertEquals(4,  $this->service->getAvailableActionPoints('navigation', $this->fleetId));
        $this->assertEquals(0,  $this->service->getAvailableActionPoints('unknown', $this->colonyId));
    }

    public function testGetConstructionPoints(): void
    {
        $this->assertGreaterThan(0, $this->service->getConstructionPoints($this->colonyId));
    }

    public function testGetResearchPoints(): void
    {
        $this->assertGreaterThan(0, $this->service->getResearchPoints($this->colonyId));
    }

    public function testGetFleetNavigationPoints(): void
    {
        $this->assertEquals(4, $this->service->getFleetNavigationPoints($this->fleetId));
    }

    public function testLockActionPoints(): void
    {
        $before = $this->service->getAvailableActionPoints('construction', $this->colonyId);
        $this->assertTrue($this->service->lockActionPoints('construction', $this->colonyId, 3));
        $this->assertEquals($before - 3, $this->service->getAvailableActionPoints('construction', $this->colonyId));

        $beforeNav = $this->service->getAvailableActionPoints('navigation', $this->fleetId);
        $this->assertTrue($this->service->lockActionPoints('navigation', $this->fleetId, 2));
        $this->assertEquals($beforeNav - 2, $this->service->getAvailableActionPoints('navigation', $this->fleetId));

        $this->assertFalse($this->service->lockActionPoints('unknown', $this->colonyId, 1));
    }

    public function testHire(): void
    {
        $advisor = $this->service->hire($this->userId, PersonellService::PERSONELL_ID_ENGINEER, $this->colonyId);
        $this->assertInstanceOf(Advisor::class, $advisor);
        $this->assertEquals($this->colonyId, $advisor->colony_id);
        $this->assertEquals(1, $advisor->rank);
        $this->assertNull($advisor->fleet_id);
    }

    public function testFire(): void
    {
        $advisor = $this->service->hire($this->userId, PersonellService::PERSONELL_ID_ENGINEER, $this->colonyId);
        $this->assertTrue($this->service->fire($advisor->id));
        $advisor->refresh();
        $this->assertNull($advisor->colony_id);
        $this->assertNull($advisor->fleet_id);
        $this->assertDatabaseHas('advisors', ['id' => $advisor->id]);  // still exists
    }

    public function testAssignToFleet(): void
    {
        $commander = $this->service->hire($this->userId, PersonellService::PERSONELL_ID_PILOT, $this->colonyId);
        $this->assertTrue($this->service->assignToFleet($commander->id, $this->fleetId));
        $commander->refresh();
        $this->assertEquals($this->fleetId, $commander->fleet_id);
        $this->assertTrue($commander->is_commander);
        $this->assertNull($commander->colony_id);
    }

    public function testAssignToFleetFailsForNonCommander(): void
    {
        $engineer = $this->service->hire($this->userId, PersonellService::PERSONELL_ID_ENGINEER, $this->colonyId);
        $this->expectException(\RuntimeException::class);
        $this->service->assignToFleet($engineer->id, $this->fleetId);
    }

    public function testUnassignFromFleet(): void
    {
        $commander = $this->service->hire($this->userId, PersonellService::PERSONELL_ID_PILOT, $this->colonyId);
        $this->service->assignToFleet($commander->id, $this->fleetId);
        $this->assertTrue($this->service->unassignFromFleet($commander->id, $this->colonyId));
        $commander->refresh();
        $this->assertEquals($this->colonyId, $commander->colony_id);
        $this->assertNull($commander->fleet_id);
        $this->assertFalse($commander->is_commander);
    }

    public function testGetColonyAdvisors(): void
    {
        $advisors = $this->service->getColonyAdvisors($this->colonyId);
        $this->assertGreaterThan(0, $advisors->count());
    }

    public function testGetFleetCommander(): void
    {
        $commander = $this->service->getFleetCommander($this->fleetId);
        $this->assertNotNull($commander);
        $this->assertTrue($commander->is_commander);
        $this->assertEquals(PersonellService::PERSONELL_ID_PILOT, $commander->personell_id);
    }
}
