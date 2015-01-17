<?php
namespace FleetTest\Service;

use CoreTest\Service\AbstractServiceTest;
use Fleet\Service\FleetService;

use Fleet\Table\FleetTable;
use Fleet\Table\FleetShipTable;
use Fleet\Table\FleetPersonellTable;
use Fleet\Table\FleetResearchTable;
use Fleet\Table\FleetOrderTable;
use Fleet\Table\FleetResourceTable;

use Fleet\Entity\Fleet;
use Fleet\Entity\FleetShip;
use Fleet\Entity\FleetPersonell;
use Fleet\Entity\FleetResearch;
use Fleet\Entity\FleetOrder;
use Fleet\Entity\FleetResource;

class FleetServiceTest extends AbstractServiceTest
{
    public function setUp()
    {
        $this->initDatabaseAdapter();

        $tables = array();
        $tables['fleet']  = new FleetTable($this->dbAdapter, new Fleet());
        $tables['fleetship'] = new FleetShipTable($this->dbAdapter, new FleetShip());
        $tables['fleetpersonell'] = new FleetPersonellTable($this->dbAdapter, new FleetPersonell());
        $tables['fleetresearch']  = new FleetResearchTable($this->dbAdapter, new FleetResearch());
        $tables['fleetorder']     = new FleetOrderTable($this->dbAdapter, new FleetOrder());
        $tables['fleetresource']  = new FleetResourceTable($this->dbAdapter, new FleetResource());

        $tables['personell'] = new \Techtree\Table\PersonellTable($this->dbAdapter, new \Techtree\Entity\Personell());
        $tables['research']  = new \Techtree\Table\ResearchTable($this->dbAdapter, new \Techtree\Entity\Research());
        $tables['ship']  = new \Techtree\Table\ShipTable($this->dbAdapter, new \Techtree\Entity\Ship());
        $tables['colony'] = new \Colony\Table\ColonyTable($this->dbAdapter, new \Colony\Entity\Colony());
        $tables['system'] = new \Galaxy\Table\SystemTable($this->dbAdapter, new \Galaxy\Entity\System());
        $tables['systemobject'] = new \Galaxy\Table\SystemObjectTable($this->dbAdapter, new \Galaxy\Entity\SystemObject());
        $tables['colonyship']      = new \Techtree\Table\ColonyShipTable($this->dbAdapter, new \Techtree\Entity\ColonyShip());
        $tables['colonypersonell'] = new \Techtree\Table\ColonyPersonellTable($this->dbAdapter, new \Techtree\Entity\ColonyPersonell());
        $tables['colonyresearch']  = new \Techtree\Table\ColonyResearchTable($this->dbAdapter, new \Techtree\Entity\ColonyResearch());
        $tables['colonyresource']  = new \Resources\Table\ColonyTable($this->dbAdapter, new \Resources\Entity\Resource());

        $serviceMocks = array();
        $colonyService   = $this->getMockBuilder('Colony\Service\ColonyService')
                                          ->disableOriginalConstructor()
                                          ->getMock();

        $colonyEntity = new \Colony\Entity\Colony();
        $colonyEntity->setId(1);
        $colonyEntity->setUserId(3);
        $colonyEntity->setCoords( array(6828,3016));
        $colonyService->expects($this->any())
                      ->method('getColony')
                      ->will($this->returnValueMap(array(
                            array(1, $colonyEntity),
                        )));

        $serviceMocks['colony'] = $colonyService;

        $tick = new \Core\Service\Tick(1234);
        #$tick->setTickCount(1234);

        $this->_service = new FleetService($tick, $tables, $serviceMocks);

        $this->fleetId = 10;
        $this->shipId = 29;
        $this->researchId = 33;
        $this->resourceId = 3;
        $this->objectId = 1;
        $this->systemId = 1;
        $this->colonyId = 1;
        $this->userId = 3;

    }

    public function testGetFleet()
    {
        $result = $this->_service->getFleet(8);
        $this->assertInstanceOf('Fleet\Entity\Fleet', $result);
        $result = $this->_service->getFleet(1);
        $this->assertFalse($result);
    }

    public function testSaveFleet()
    {
        $this->markTestIncomplete();


        $this->initDatabase();
    }

    public function testSaveFleetOrder()
    {
        $this->markTestIncomplete();

        $this->initDatabase();
    }

    public function testGetFleetOrdersByFleetIds()
    {
        $this->markTestIncomplete();
    }

    public function testAddOrder()
    {
        $this->initDatabase();
        $this->markTestIncomplete();
    }

    public function testTransferShip()
    {
        $this->initDatabase();

        $fleetentityTable  = $this->_service->getTable('fleetship');
        $fleetentityPK = array('fleet_id'=> $this->fleetId, 'ship_id'=>37);
        $fleetentity = $fleetentityTable->getEntity($fleetentityPK);
        $entityCountInFleetBefore = !empty($fleetentity) ? $fleetentity->getCount() : 0;

        $colonyentityTable = $this->_service->getTable('colonyship');
        $colonyentityPK = array('colony_id'=> $this->colonyId, 'ship_id'=>37);
        $colonyentity = $colonyentityTable->getEntity($colonyentityPK);
        $entityCountOnColonyBefore = !empty($colonyentity) ? $colonyentity->getLevel() : 0;

        #var_dump($this->colonyId);
        #var_dump($this->fleetId);
        $transferedItemCount = $this->_service->transferShip($this->colonyId, $this->fleetId, 37, 6);
        $this->assertEquals(6, $transferedItemCount);

        $fleetentity = $fleetentityTable->getEntity($fleetentityPK);
        $entityCountInFleetAfter = !empty($fleetentity) ? $fleetentity->getCount() : 0;

        $colonyentity = $colonyentityTable->getEntity($colonyentityPK);
        $entityCountOnColonyAfter = !empty($colonyentity) ? $colonyentity->getLevel() : 0;

        $this->assertTrue($entityCountOnColonyBefore == $entityCountOnColonyAfter + 6);
        $this->assertTrue($entityCountInFleetBefore + 6 == $entityCountInFleetAfter);

        $transferedItemCount = $this->_service->transferShip($this->colonyId, $this->fleetId, 37, 5);
        $this->assertEquals(3, $transferedItemCount);

        $fleetentity = $fleetentityTable->getEntity($fleetentityPK);
        $entityCountInFleetAfter = !empty($fleetentity) ? $fleetentity->getCount() : 0;

        $colonyentity = $colonyentityTable->getEntity($colonyentityPK);
        $entityCountOnColonyAfter = !empty($colonyentity) ? $colonyentity->getLevel() : 0;

        $this->assertTrue($entityCountOnColonyBefore == $entityCountOnColonyAfter + 9);
        $this->assertTrue($entityCountInFleetBefore + 9 == $entityCountInFleetAfter);

    }

    public function testTransferResearch()
    {
        $this->initDatabase();

        // initial personell count for research id 81 on colony id 1 is 16

        $transferedItemCount = $this->_service->transferResearch($this->colonyId, $this->fleetId, 81, 5);
        $this->assertEquals(5, $transferedItemCount);

        $transferedItemCount = $this->_service->transferResearch($this->colonyId, $this->fleetId, 81, 15);
        $this->assertEquals(11, $transferedItemCount);

        $transferedItemCount = $this->_service->transferResearch($this->colonyId, $this->fleetId, 81, -16);
        $this->assertEquals(16, $transferedItemCount);

    }

    public function testTransferPersonell()
    {

        $this->initDatabase();

        // initial personell count for personell id 36 on colony id 1 is 2
        // initial personell count for personell id 36 on fleet id 10 is 2

        $transferedItemCount = $this->_service->transferPersonell($this->colonyId, $this->fleetId, 36, -5);
        $this->assertEquals(2, $transferedItemCount);

        $transferedItemCount = $this->_service->transferPersonell($this->colonyId, $this->fleetId, 36, 10);
        $this->assertEquals(4, $transferedItemCount);

        $transferedItemCount = $this->_service->transferPersonell($this->colonyId, $this->fleetId, 36, -2);
        $this->assertEquals(2, $transferedItemCount);
    }

    public function testTransferTechnology()
    {
        $this->initDatabase();
        $this->markTestIncomplete();
    }

    public function testTransferResource()
    {

#        $this->initDatabase();
#
#        // initial resource amount for resource id 12 on colony id 1 is 9500
#
#        $transferedItemCount = $this->_service->transferResource($this->colonyId, $this->fleetId, 12, -20000);
#        $this->assertEquals(9500, $transferedItemCount);
#
#        $transferedItemCount = $this->_service->transferResource($this->colonyId, $this->fleetId, 12, 5000);
#        $this->assertEquals(5000, $transferedItemCount);
#
#        $transferedItemCount = $this->_service->transferResource($this->colonyId, $this->fleetId, 12, 5000);
#        $this->assertEquals(4500, $transferedItemCount);

        // TODO: it seems that update on existing rows fails with duplicate key errors

        $this->markTestSkipped();
    }

    public function testGetFleetShip()
    {
        $result = $this->_service->getFleetShip(array('fleet_id' => $this->fleetId, 'ship_id' => $this->shipId));
        $this->assertInstanceOf('Fleet\Entity\FleetShip', $result);
        $result = $this->_service->getFleetShip(array('fleet_id' => $this->fleetId, 'ship_id' => 0));
        $this->assertFalse($result);
        $result = $this->_service->getFleetShip(array('fleet_id' => $this->fleetId, 'ship_id' => 0), true);
        $this->assertInstanceOf('Fleet\Entity\FleetShip', $result);
    }

    public function testGetFleetResearch()
    {
        $result = $this->_service->getFleetResearch(array('fleet_id' => $this->fleetId, 'research_id' => $this->researchId));
        $this->assertInstanceOf('Fleet\Entity\FleetResearch', $result);
        $result = $this->_service->getFleetResearch(array('fleet_id' => $this->fleetId, 'research_id' => 0));
        $this->assertFalse($result);
        $result = $this->_service->getFleetResearch(array('fleet_id' => $this->fleetId, 'research_id' => 0), true);
        $this->assertInstanceOf('Fleet\Entity\FleetResearch', $result);
        $this->markTestIncomplete();
    }

    public function testGetFleetShips()
    {
        $result = $this->_service->getFleetShips(array('fleet_id' => $this->fleetId));
        $this->assertInstanceOf('Core\Model\ResultSet', $result);
        $this->assertTrue($result->count() == 5);
    }

    public function testGetFleetShipsByFleetId()
    {
        $result = $this->_service->getFleetShipsByFleetId($this->fleetId);
        $this->assertInstanceOf('Core\Model\ResultSet', $result);
        $this->assertTrue($result->count() == 5);
    }

    public function testGetFleetResearches()
    {
        $result = $this->_service->getFleetResearches(array('fleet_id' => $this->fleetId));
        $this->assertInstanceOf('Core\Model\ResultSet', $result);
        $this->assertTrue($result->count() == 8);
    }

    public function testGetFleetResearchesByFleetId()
    {
        $result = $this->_service->getFleetResearchesByFleetId($this->fleetId);
        $this->assertInstanceOf('Core\Model\ResultSet', $result);
        $this->assertTrue($result->count() == 8);
    }

    public function testGetFleetPersonell()
    {
        $result = $this->_service->getFleetPersonell(array('fleet_id' => $this->fleetId));
        $this->assertInstanceOf('Core\Model\ResultSet', $result);
        $this->assertTrue($result->count() == 3);
    }

    public function testGetFleetPersonellByFleetId()
    {
        $result = $this->_service->getFleetPersonellByFleetId($this->fleetId);
        $this->assertInstanceOf('Core\Model\ResultSet', $result);
        $this->assertTrue($result->count() == 3);
    }

    public function testGetFleetResources()
    {
        $result = $this->_service->getFleetResources(array('fleet_id' => $this->fleetId));
        $this->assertInstanceOf('Core\Model\ResultSet', $result);
        $this->assertTrue($result->count() == 6);
    }

    public function testGetFleetResourcesByFleetId()
    {
        $result = $this->_service->getFleetResourcesByFleetId($this->fleetId);
        $this->assertInstanceOf('Core\Model\ResultSet', $result);
        $this->assertTrue($result->count() == 6);
    }

    public function testGetFleetResource()
    {
        $result = $this->_service->getFleetResource(array('fleet_id' => $this->fleetId, 'resource_id' => $this->resourceId));
        $this->assertInstanceOf('Fleet\Entity\FleetResource', $result);

        $result = $this->_service->getFleetResource(array('fleet_id' => $this->fleetId, 'resource_id' => 99));
        $this->assertFalse($result);

        $result = $this->_service->getFleetResource(array('fleet_id' => 99, 'resource_id' => $this->resourceId));
        $this->assertFalse($result);

        $this->setExpectedException('Exception');
        $this->_service->getFleetResource(-1);
    }

    public function testGetOrders()
    {
        $result = $this->_service->getOrders(array('fleet_id' => $this->fleetId));
        $this->assertInstanceOf('Core\Model\ResultSet', $result);
        $this->assertInstanceOf('Fleet\Entity\FleetOrder', $result->current());
        $this->markTestIncomplete();
    }

    public function testGetFleetsByUserId()
    {
        $result = $this->_service->getFleetsByUserId($this->userId);
        $this->assertInstanceOf('Core\Model\ResultSet', $result);
        $this->assertInstanceOf('Fleet\Entity\Fleet', $result->current());

        $result = $this->_service->getFleetsByUserId(99);
        $this->assertInstanceOf('Core\Model\ResultSet', $result);
        $this->assertFalse($result->current());

        $this->setExpectedException('Core\Service\Exception');
        $this->_service->getFleetsByUserId(-1);
    }

    public function testGetFleetsByEntityId()
    {
        $result = $this->_service->getFleetsByEntityId('colony', $this->colonyId);
        $this->assertInstanceOf('Core\Model\ResultSet', $result);
        $this->assertInstanceOf('Fleet\Entity\Fleet', $result->current());

        $result = $this->_service->getFleetsByEntityId('object', $this->objectId);
        $this->assertInstanceOf('Core\Model\ResultSet', $result);
        $this->assertInstanceOf('Fleet\Entity\Fleet', $result->current());

        $result = $this->_service->getFleetsByEntityId('system', $this->systemId);
        $this->assertInstanceOf('Core\Model\ResultSet', $result);
        $this->assertInstanceOf('Fleet\Entity\Fleet', $result->current());

        $this->markTestIncomplete();
    }

    public function testGetFleetTechnologies()
    {
        $result = $this->_service->getFleetTechnologies($this->fleetId);
        $this->assertTrue(is_array($result));
        $this->assertArrayHasKey('research', $result);
        $this->assertTrue(is_array($result['research']));
        $this->assertArrayHasKey('personell', $result);
        $this->assertTrue(is_array($result['personell']));
        $this->assertArrayHasKey('ship', $result);
        $this->assertTrue(is_array($result['ship']));
    }
}