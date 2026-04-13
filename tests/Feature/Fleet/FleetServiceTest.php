<?php

namespace Tests\Feature\Fleet;

use App\Models\Fleet;
use App\Models\FleetOrder;
use App\Models\FleetPersonell;
use App\Models\FleetResearch;
use App\Models\FleetResource;
use App\Models\FleetShip;
use App\Services\FleetService;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FleetServiceTest extends TestCase
{
    use RefreshDatabase;

    protected FleetService $service;

    // Test constants matching the Simpsons fixture in TestSeeder
    protected int $fleetId    = 10;
    protected int $shipId     = 37;   // korvette (ex fighter1)
    protected int $researchId = 90;
    protected int $resourceId = 4;
    protected int $objectId   = 1;
    protected int $systemId   = 1;
    protected int $colonyId   = 1;
    protected int $userId     = 3;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
        $this->service = $this->app->make(FleetService::class);
    }

    public function testGetFleet(): void
    {
        $result = $this->service->getFleet(8);
        $this->assertInstanceOf(Fleet::class, $result);

        $result = $this->service->getFleet(1);
        $this->assertFalse($result);
    }

    public function testSaveFleet(): void
    {
        $fleet = $this->service->getFleet(8);
        $this->assertInstanceOf(Fleet::class, $fleet);

        $fleet->fleet = 'UpdatedFleetName';
        $this->service->saveFleet($fleet);

        $reloaded = $this->service->getFleet(8);
        $this->assertEquals('UpdatedFleetName', $reloaded->fleet);
    }

    public function testSaveFleetOrder(): void
    {
        $before = $this->service->getOrders(['fleet_id' => $this->fleetId])->count();

        $order              = new FleetOrder();
        $order->tick        = 99999;
        $order->fleet_id    = $this->fleetId;
        $order->order       = 'hold';
        $order->coordinates = '[6828,3016,0]';
        $this->service->saveFleetOrder($order);

        $after = $this->service->getOrders(['fleet_id' => $this->fleetId])->count();
        $this->assertEquals($before + 1, $after);
    }

    public function testGetFleetOrdersByFleetIds(): void
    {
        $result = $this->service->getFleetOrdersByFleetIds([$this->fleetId]);
        $this->assertInstanceOf(FleetOrder::class, $result->first());
        $this->assertEquals(21, $result->count());

        // fleet 11 has 0 orders — total stays 21
        $result = $this->service->getFleetOrdersByFleetIds([10, 11]);
        $this->assertEquals(21, $result->count());
    }

    public function testAddOrder(): void
    {
        $this->markTestSkipped(
            'addOrder() delegates to GalaxyService::getPath() — requires complex service wiring not tested here.'
        );
    }

    public function testTransferShip(): void
    {
        // colony 1, ship_id 37: check initial level on colony and count in fleet
        $colonyBefore = \Illuminate\Support\Facades\DB::table('colony_ships')
            ->where(['colony_id' => $this->colonyId, 'ship_id' => 37])->first();
        $fleetBefore = \Illuminate\Support\Facades\DB::table('fleet_ships')
            ->where(['fleet_id' => $this->fleetId, 'ship_id' => 37, 'is_cargo' => 0])->first();

        $colonyCountBefore = $colonyBefore ? $colonyBefore->level : 0;
        $fleetCountBefore  = $fleetBefore  ? $fleetBefore->count  : 0;

        // Transfer 6 — should succeed fully
        $transferred = $this->service->transferShip($this->colonyId, $this->fleetId, 37, 6);
        $this->assertEquals(6, $transferred);

        $colonyAfter = \Illuminate\Support\Facades\DB::table('colony_ships')
            ->where(['colony_id' => $this->colonyId, 'ship_id' => 37])->first();
        $fleetAfter = \Illuminate\Support\Facades\DB::table('fleet_ships')
            ->where(['fleet_id' => $this->fleetId, 'ship_id' => 37, 'is_cargo' => 0])->first();

        $this->assertEquals($colonyCountBefore - 6, $colonyAfter->level);
        $this->assertEquals($fleetCountBefore + 6, $fleetAfter->count);

        // Transfer 5 more — only (colonyCountBefore - 6) remain on colony
        $remaining   = $colonyCountBefore - 6;
        $expect      = min(5, $remaining);
        $transferred = $this->service->transferShip($this->colonyId, $this->fleetId, 37, 5);
        $this->assertEquals($expect, $transferred);
    }

    public function testTransferResearch(): void
    {
        // initial level for research_id=90 (construction) on colony_id=1 is 16 (from test data)
        $transferred = $this->service->transferResearch($this->colonyId, $this->fleetId, 90, 5);
        $this->assertEquals(5, $transferred);

        $transferred = $this->service->transferResearch($this->colonyId, $this->fleetId, 90, 15);
        $this->assertEquals(11, $transferred); // only 11 remain (16-5)

        // transfer back all 16
        $transferred = $this->service->transferResearch($this->colonyId, $this->fleetId, 90, -16);
        $this->assertEquals(16, $transferred);
    }

    public function testTransferPersonell(): void
    {
        // initial personell_id=36: colony=2, fleet=2 (from test data)
        $transferred = $this->service->transferPersonell($this->colonyId, $this->fleetId, 36, -5);
        $this->assertEquals(2, $transferred); // only 2 in fleet

        $transferred = $this->service->transferPersonell($this->colonyId, $this->fleetId, 36, 10);
        $this->assertEquals(4, $transferred); // 2+2=4 on colony, all 4 transfer

        $transferred = $this->service->transferPersonell($this->colonyId, $this->fleetId, 36, -2);
        $this->assertEquals(2, $transferred);
    }

    public function testTransferTechnology(): void
    {
        // research type, colony 1, fleet 10, research_id 90 (construction)
        $result = $this->service->transferTechnology('research', $this->colonyId, $this->fleetId, 90, 5);
        $this->assertEquals(5, $result);

        $result = $this->service->transferTechnology('research', $this->colonyId, $this->fleetId, 90, -5);
        $this->assertEquals(5, $result);

        $this->expectException(\InvalidArgumentException::class);
        $this->service->transferTechnology('invalid', $this->colonyId, $this->fleetId, 90, 1);
    }

    public function testTransferResource(): void
    {
        $this->markTestSkipped('transferResource() has known duplicate-key issues in test data — skipped like original.');
    }

    public function testGetFleetShip(): void
    {
        $result = $this->service->getFleetShip(['fleet_id' => $this->fleetId, 'ship_id' => $this->shipId]);
        $this->assertInstanceOf(FleetShip::class, $result);

        $result = $this->service->getFleetShip(['fleet_id' => $this->fleetId, 'ship_id' => 0]);
        $this->assertFalse($result);

        $result = $this->service->getFleetShip(['fleet_id' => $this->fleetId, 'ship_id' => 0], true);
        $this->assertInstanceOf(FleetShip::class, $result);
        $this->assertEquals(0, $result->count);
    }

    public function testGetFleetResearch(): void
    {
        $result = $this->service->getFleetResearch(['fleet_id' => $this->fleetId, 'research_id' => $this->researchId]);
        $this->assertInstanceOf(FleetResearch::class, $result);

        $result = $this->service->getFleetResearch(['fleet_id' => $this->fleetId, 'research_id' => 0]);
        $this->assertFalse($result);

        $result = $this->service->getFleetResearch(['fleet_id' => $this->fleetId, 'research_id' => 0], true);
        $this->assertInstanceOf(FleetResearch::class, $result);
        $this->assertEquals(0, $result->count);
    }

    public function testGetFleetShips(): void
    {
        $result = $this->service->getFleetShips(['fleet_id' => $this->fleetId]);
        $this->assertEquals(1, $result->count()); // fleet 10 has only korvette (37) after Phase 3a cleanup
    }

    public function testGetFleetShipsByFleetId(): void
    {
        $result = $this->service->getFleetShipsByFleetId($this->fleetId);
        $this->assertEquals(1, $result->count()); // fleet 10 has only korvette (37) after Phase 3a cleanup
    }

    public function testGetFleetResearches(): void
    {
        $result = $this->service->getFleetResearches(['fleet_id' => $this->fleetId]);
        $this->assertEquals(2, $result->count());
    }

    public function testGetFleetResearchesByFleetId(): void
    {
        $result = $this->service->getFleetResearchesByFleetId($this->fleetId);
        $this->assertEquals(2, $result->count());
    }

    public function testGetFleetPersonell(): void
    {
        $result = $this->service->getFleetPersonell(['fleet_id' => $this->fleetId]);
        $this->assertEquals(3, $result->count());
    }

    public function testGetFleetPersonellByFleetId(): void
    {
        $result = $this->service->getFleetPersonellByFleetId($this->fleetId);
        $this->assertEquals(3, $result->count());
    }

    public function testGetFleetResources(): void
    {
        $result = $this->service->getFleetResources(['fleet_id' => $this->fleetId]);
        $this->assertEquals(2, $result->count());
    }

    public function testGetFleetResourcesByFleetId(): void
    {
        $result = $this->service->getFleetResourcesByFleetId($this->fleetId);
        $this->assertEquals(2, $result->count());
    }

    public function testGetFleetResource(): void
    {
        $result = $this->service->getFleetResource(['fleet_id' => $this->fleetId, 'resource_id' => $this->resourceId]);
        $this->assertInstanceOf(FleetResource::class, $result);

        $result = $this->service->getFleetResource(['fleet_id' => $this->fleetId, 'resource_id' => 99]);
        $this->assertFalse($result);

        $result = $this->service->getFleetResource(['fleet_id' => 99, 'resource_id' => $this->resourceId]);
        $this->assertFalse($result);
    }

    public function testGetOrders(): void
    {
        $result = $this->service->getOrders(['fleet_id' => $this->fleetId]);
        $this->assertNotEmpty($result);
        $this->assertInstanceOf(FleetOrder::class, $result->first());
    }

    public function testGetFleetsByUserId(): void
    {
        $result = $this->service->getFleetsByUserId($this->userId);
        $this->assertTrue($result->isNotEmpty());
        $this->assertInstanceOf(Fleet::class, $result->first());

        $result = $this->service->getFleetsByUserId(99);
        $this->assertTrue($result->isEmpty());

        $this->expectException(\InvalidArgumentException::class);
        $this->service->getFleetsByUserId(-1);
    }

    public function testGetFleetsByEntityId(): void
    {
        $result = $this->service->getFleetsByEntityId('colony', $this->colonyId);
        $this->assertTrue($result->isNotEmpty());
        $this->assertInstanceOf(Fleet::class, $result->first());

        $result = $this->service->getFleetsByEntityId('object', $this->objectId);
        $this->assertTrue($result->isNotEmpty());
        $this->assertInstanceOf(Fleet::class, $result->first());

        $result = $this->service->getFleetsByEntityId('system', $this->systemId);
        $this->assertTrue($result->isNotEmpty());
        $this->assertInstanceOf(Fleet::class, $result->first());

        $this->expectException(\InvalidArgumentException::class);
        $this->service->getFleetsByEntityId('colony', -1);
    }

    public function testGetFleetTechnologies(): void
    {
        $result = $this->service->getFleetTechnologies($this->fleetId);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('research', $result);
        $this->assertIsArray($result['research']);
        $this->assertArrayHasKey('ship', $result);
        $this->assertIsArray($result['ship']);
        $this->assertArrayHasKey('personell', $result);
        $this->assertIsArray($result['personell']);
    }
}
