<?php

namespace Tests\Feature\Techtree;

use App\Services\Techtree\BuildingService;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BuildingServiceTest extends TestCase
{
    use RefreshDatabase;

    protected BuildingService $service;
    protected int $entityId  = 27; // oremine
    protected int $colonyId  = 1;
    protected int $colonyId2 = 2;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
        $this->service = $this->app->make(BuildingService::class);
    }

    public function testGetEntities(): void
    {
        $result = $this->service->getEntities();
        $this->assertTrue($result->isNotEmpty());
    }

    public function testGetEntity(): void
    {
        $result = $this->service->getEntity($this->entityId);
        $this->assertNotNull($result);
        $this->assertEquals(27, $result->id);
        $this->assertFalse($this->service->getEntity(99999));

        $this->expectException(\InvalidArgumentException::class);
        $this->service->getEntity(-1);
    }

    public function testGetEntityCosts(): void
    {
        $result = $this->service->getEntityCosts($this->entityId);
        $this->assertTrue($result->isNotEmpty());
    }

    public function testGetColonyEntity(): void
    {
        $result = $this->service->getColonyEntity($this->colonyId, $this->entityId);
        $this->assertNotNull($result);
        $this->assertEquals(5, $result->level);  // oremine level=5 on colony 1 per test data
    }

    public function testGetColonyEntities(): void
    {
        $result = $this->service->getColonyEntities($this->colonyId);
        $this->assertTrue($result->isNotEmpty());
    }

    public function testCheckRequiredActionPoints(): void
    {
        // oremine (27): ap_spend=10, ap_for_levelup=10 -> passes
        $this->assertTrue($this->service->checkRequiredActionPoints($this->colonyId, 27));
        // housingComplex (28): ap_spend=0, ap_for_levelup=10 -> fails
        $this->assertFalse($this->service->checkRequiredActionPoints($this->colonyId, 28));
        // colony 2, oremine: ap_spend=1, ap_for_levelup=10 -> fails
        $this->assertFalse($this->service->checkRequiredActionPoints($this->colonyId2, 27));
    }

    public function testLevelup(): void
    {
        $before = $this->service->getColonyEntity($this->colonyId, $this->entityId);
        $result = $this->service->levelup($this->colonyId, $this->entityId);
        $this->assertTrue($result);
        $after = $this->service->getColonyEntity($this->colonyId, $this->entityId);
        $this->assertEquals($before->level + 1, $after->level);

        // housingComplex (28): ap_spend=0 fails AP check
        $result = $this->service->levelup($this->colonyId, 28);
        $this->assertFalse($result);

        // colony 2, oremine: ap_spend=1 fails AP check
        $result = $this->service->levelup($this->colonyId2, $this->entityId);
        $this->assertFalse($result);
    }

    public function testInvest(): void
    {
        // With real PersonellService: engineer level=9 -> totalAP=50, no locked AP -> availableAP=50
        // oremine ap_spend already=10 (max), invest returns true (no effective change but succeeds)
        $result = $this->service->invest($this->colonyId, $this->entityId, 'add', 1);
        $this->assertTrue($result);

        $this->expectException(\InvalidArgumentException::class);
        $this->service->invest(-1, $this->entityId);
    }
}
