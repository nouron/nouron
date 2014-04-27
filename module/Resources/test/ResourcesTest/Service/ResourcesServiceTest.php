<?php
namespace ResourcesTest\Service;

use NouronTest\Service\AbstractServiceTest;
use Nouron\Service\Tick;
use Resources\Service\ResourcesService;
use Resources\Table\ResourceTable;
use Resources\Table\ColonyTable;
use Resources\Table\UserTable;
use Resources\Entity\Colony;
use Resources\Entity\Resource;
use Resources\Entity\User;

use Techtree\Table\BuildingCostTable;
use Techtree\Entity\BuildingCost;

class ResourcesServiceTest extends AbstractServiceTest
{
    public function setUp()
    {
        $this->initDatabaseAdapter();

        $tables = array();
        $tables['resource'] = new ResourceTable($this->dbAdapter, new Resource());
        $tables['colonyresources'] = new ColonyTable($this->dbAdapter, new Colony());
        $tables['userresources'] = new UserTable($this->dbAdapter, new User());

        $tick = new Tick(16000);

        $services = array();
        $galaxyService = $this->getMockBuilder('Galaxy\Service\Gateway')
                              ->disableOriginalConstructor()
                              ->getMock();
        $galaxyService->expects($this->any())
                      ->method('checkColonyOwner')
                      ->will($this->returnValueMap(array(
                            array(1,99, false),
                            array(1,3, true)
                        )));

        $colonyEntity = new \Galaxy\Entity\Colony();
        $colonyEntity->exchangeArray(array('id'=>1,'user_id'=>3));
        $galaxyService->expects($this->any())
                      ->method('getColony')
                      ->will($this->returnValueMap(array(
                            array(1, $colonyEntity),
                        )));
        $services['galaxy'] = $galaxyService;

        $this->_service = new ResourcesService($tick, $tables, $services);
        $logger = $this->getMockBuilder('Zend\Log\Logger')
                       ->disableOriginalConstructor()
                       ->getMock();
        $this->_service->setLogger($logger);
    }

    public function testGatewayInitialState()
    {
        $this->assertInstanceOf('Resources\Service\ResourcesService', $this->_service);
        $this->assertInstanceOf('Resources\Table\ResourceTable', $this->_service->getTable('resource'));
        $this->assertInstanceOf('Resources\Table\ColonyTable', $this->_service->getTable('colonyresources'));
        $this->assertInstanceOf('Resources\Table\UserTable', $this->_service->getTable('userresources'));
        $this->assertEquals('integer', gettype($this->_service->getTick()));
    }

    public function testGetColonyResources()
    {
        $results = $this->_service->getColonyResources();
        $this->assertInstanceOf('Nouron\Model\ResultSet', $results);
        $this->assertInstanceOf('Resources\Entity\Colony', $results->current());
    }

    public function testGetUserResources()
    {
        $results = $this->_service->getUserResources();
        $this->assertInstanceOf('Nouron\Model\ResultSet', $results);
        $this->assertInstanceOf('Resources\Entity\User', $results->current());
    }

    public function getPossessionsByColonyId()
    {
        $result = $this->_service->getPossessionsByColonyId($colonyId);
        $this->assertTrue(is_array($result));
    }

    public function testCheck()
    {
        $entityId = 52;
        $colonyId = 1;
        $buildingCostsTable = new BuildingCostTable($this->dbAdapter, new BuildingCost());
        $costs =  $buildingCostsTable->fetchAll(array('building_id' => $entityId));
        $result = $this->_service->check($costs, $colonyId);
        $this->assertTrue($result);
    }

    public function testPayCosts()
    {
        $this->initDatabase();
        $entityId = 52;
        $colonyId = 1;
        $buildingCostsTable = new BuildingCostTable($this->dbAdapter, new BuildingCost());
        $costs =  $buildingCostsTable->fetchAll(array('building_id' => $entityId));
        $result = $this->_service->payCosts($costs, $colonyId);
        $this->assertTrue($result);
        $this->markTestIncomplete();
    }

    public function testIncreaseAmount()
    {
        $this->initDatabase();
        $colonyId = 1;
        $resId = 2;
        $amount = 100;
        $forceUserResToBeColRes = false;
        $result = $this->_service->increaseAmount($colonyId, $resId, $amount, $forceUserResToBeColRes);
        $this->assertTrue(is_numeric($result));
        $this->markTestIncomplete();
    }

    public function testDecreaseAmount()
    {
        $this->initDatabase();
        $colonyId = 1;
        $resId = 2;
        $amount = 100;
        $result = $this->_service->decreaseAmount($colonyId, $resId, $amount);
        $this->assertTrue(is_numeric($result));
        $this->markTestIncomplete();
    }

}