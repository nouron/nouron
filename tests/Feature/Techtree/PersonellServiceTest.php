<?php

namespace Tests\Feature\Techtree;

use App\Services\Techtree\PersonellService;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PersonellServiceTest extends TestCase
{
    use RefreshDatabase;

    protected PersonellService $service;
    protected int $colonyId  = 1;
    protected int $colonyId2 = 2;

    /**
     * Fleet ID 10 has a commander (personell_id=89) with count=1 in the test DB.
     * Fleet ID 17 has a commander with count=8.
     */
    protected int $fleetId  = 10;
    protected int $fleetId2 = 17;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
        $this->service = $this->app->make(PersonellService::class);
    }

    public function testGetEntities(): void
    {
        $this->assertTrue($this->service->getEntities()->isNotEmpty());
    }

    public function testGetEntity(): void
    {
        $result = $this->service->getEntity(PersonellService::PERSONELL_ID_ENGINEER);
        $this->assertNotNull($result);
        $this->assertEquals(35, $result->id);
    }

    public function testGetColonyEntity(): void
    {
        $result = $this->service->getColonyEntity($this->colonyId, PersonellService::PERSONELL_ID_ENGINEER);
        $this->assertEquals(9, $result->level);  // engineer level=9 on colony 1 per test data
    }

    public function testGetTotalActionPoints(): void
    {
        // engineer level=9 -> 9*5+5 = 50
        $this->assertGreaterThan(
            PersonellService::DEFAULT_ACTIONPOINTS,
            $this->service->getTotalActionPoints('construction', $this->colonyId)
        );
        $this->assertGreaterThan(
            PersonellService::DEFAULT_ACTIONPOINTS,
            $this->service->getTotalActionPoints('research', $this->colonyId)
        );

        // Navigation is now fleet-scoped: fleetId=10 has commander count=1 -> 1*5+5 = 10
        $this->assertEquals(
            10,
            $this->service->getTotalActionPoints('navigation', $this->fleetId)
        );

        // fleetId=17 has commander count=8 -> 8*5+5 = 45
        $this->assertEquals(
            45,
            $this->service->getTotalActionPoints('navigation', $this->fleetId2)
        );
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
        // fleetId=10, commander count=1 -> totalAP = 1*5+5 = 10, none locked -> available = 10
        $this->assertEquals(10, $this->service->getFleetNavigationPoints($this->fleetId));

        // fleetId=17, commander count=8 -> totalAP = 8*5+5 = 45
        $this->assertEquals(45, $this->service->getFleetNavigationPoints($this->fleetId2));
    }

    public function testGetAvailableActionPoints(): void
    {
        $this->assertGreaterThan(0, $this->service->getAvailableActionPoints('construction', $this->colonyId));
        $this->assertGreaterThan(0, $this->service->getAvailableActionPoints('research', $this->colonyId));

        // Navigation is fleet-scoped: fleetId=10 has commander count=1 -> 1*5+5 = 10, none locked
        $this->assertEquals(
            10,
            $this->service->getAvailableActionPoints('navigation', $this->fleetId)
        );

        // unknown type returns 0
        $this->assertEquals(0, $this->service->getAvailableActionPoints('unknown_type', $this->colonyId));
    }

    public function testLockActionPoints(): void
    {
        $before = $this->service->getAvailableActionPoints('construction', $this->colonyId);
        $this->service->lockActionPoints('construction', $this->colonyId, 3);
        $after = $this->service->getAvailableActionPoints('construction', $this->colonyId);
        $this->assertEquals($before - 3, $after);

        // Navigation AP are fleet-scoped: lock against fleetId, not colonyId
        $beforeNav = $this->service->getAvailableActionPoints('navigation', $this->fleetId);
        $this->service->lockActionPoints('navigation', $this->fleetId, 2);
        $afterNav = $this->service->getAvailableActionPoints('navigation', $this->fleetId);
        $this->assertEquals($beforeNav - 2, $afterNav);

        // unknown type returns false
        $this->assertFalse($this->service->lockActionPoints('unknown_type', $this->colonyId, 1));
    }

    public function testInvest(): void
    {
        $result = $this->service->invest($this->colonyId, PersonellService::PERSONELL_ID_ENGINEER);
        $this->assertFalse($result);  // PersonellService::invest() always returns false
    }

    public function testHire(): void
    {
        $before = $this->service->getColonyEntity($this->colonyId, PersonellService::PERSONELL_ID_ENGINEER)->level;
        $this->assertTrue($this->service->hire($this->colonyId, PersonellService::PERSONELL_ID_ENGINEER));
        $after = $this->service->getColonyEntity($this->colonyId, PersonellService::PERSONELL_ID_ENGINEER)->level;
        $this->assertEquals($before + 1, $after);
    }

    public function testFire(): void
    {
        $before = $this->service->getColonyEntity($this->colonyId, PersonellService::PERSONELL_ID_ENGINEER)->level;
        $this->assertTrue($this->service->fire($this->colonyId, PersonellService::PERSONELL_ID_ENGINEER));
        $after = $this->service->getColonyEntity($this->colonyId, PersonellService::PERSONELL_ID_ENGINEER)->level;
        $this->assertEquals($before - 1, $after);

        // pilot at level 0 cannot be fired (colony_personell pilot level is 0)
        $this->assertFalse($this->service->fire($this->colonyId, PersonellService::PERSONELL_ID_PILOT));
    }
}
