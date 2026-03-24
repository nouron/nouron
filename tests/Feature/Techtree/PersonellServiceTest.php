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
        // pilot level=0 -> 0*5+5 = 5 = DEFAULT
        $this->assertEquals(
            PersonellService::DEFAULT_ACTIONPOINTS,
            $this->service->getTotalActionPoints('navigation', $this->colonyId)
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

    public function testGetNavigationPoints(): void
    {
        $this->assertEquals(0, $this->service->getNavigationPoints($this->colonyId));
    }

    public function testGetAvailableActionPoints(): void
    {
        $this->assertGreaterThan(0, $this->service->getAvailableActionPoints('construction', $this->colonyId));
        $this->assertGreaterThan(0, $this->service->getAvailableActionPoints('research', $this->colonyId));
        $this->assertEquals(0, $this->service->getAvailableActionPoints('navigation', $this->colonyId));
    }

    public function testLockActionPoints(): void
    {
        $before = $this->service->getAvailableActionPoints('construction', $this->colonyId);
        $this->service->lockActionPoints('construction', $this->colonyId, 3);
        $after = $this->service->getAvailableActionPoints('construction', $this->colonyId);
        $this->assertEquals($before - 3, $after);
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

        // pilot at level 0 cannot be fired
        $this->assertFalse($this->service->fire($this->colonyId, PersonellService::PERSONELL_ID_PILOT));
    }
}
