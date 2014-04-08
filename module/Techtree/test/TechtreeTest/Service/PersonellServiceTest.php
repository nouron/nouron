<?php
namespace TechtreeTest\Service;

use Techtree\Service\PersonellService;
use PHPUnit_Framework_TestCase;
use Techtree\Table\PersonellTable;
use Techtree\Table\PersonellCostTable;
use Techtree\Table\ColonyPersonellTable;
use Techtree\Table\ActionPointTable;
use Techtree\Entity\Personell;
use Techtree\Entity\PersonellCost;
use Techtree\Entity\ColonyPersonell;
use Techtree\Entity\ActionPoint;

class PersonellServiceTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $dbAdapter = new \Zend\Db\Adapter\Adapter(
            array(
                'driver' => 'Pdo_Sqlite',
                'database' => '../data/db/test.db'
            )
        );

        $tableMocks = array();
        $tableMocks['personell'] = new PersonellTable($dbAdapter, new Personell());
        $tableMocks['personell_costs']   = new PersonellCostTable($dbAdapter, new PersonellCost());
        $tableMocks['colony_personell'] = new ColonyPersonellTable($dbAdapter, new ColonyPersonell());
        $tableMocks['locked_actionpoints'] = new ActionPointTable($dbAdapter, new ActionPoint());

        $tick = new \Nouron\Service\Tick(1234);
        #$tick->setTickCount(1234);

        $serviceMocks = array();
        $serviceMocks['resources'] = $this->getMockBuilder('Resources\Service\ResourcesService')
                                          ->disableOriginalConstructor()
                                          ->getMock();
        $serviceMocks['galaxy']    = $this->getMockBuilder('Galaxy\Service\Gateway')
                                          ->disableOriginalConstructor()
                                          ->getMock();

        $this->_service = new PersonellService($tick, $tableMocks, $serviceMocks);

        // default test parameters
        $this->_entityId = 35;
        $this->_colonyId = 1;
    }

    public function testCheckRequiredActionPoints()
    {
        $result = $this->_service->checkRequiredActionPoints($this->_colonyId, $this->_entityId);
        $this->assertTrue($result);
    }

    public function testGetEntityCosts()
    {
        $objects = $this->_service->getEntityCosts($this->_entityId);
        $this->assertTrue(!empty($objects));
        $this->assertEquals('Nouron\Model\ResultSet', get_class($objects));
        $this->assertEquals('Techtree\Entity\PersonellCost', get_class($objects->current()));
    }

    public function testColonyEntity()
    {
        $possess = $this->_service->getColonyEntity($this->_colonyId, $this->_entityId);
        $this->assertEquals(9, $possess->getLevel());
    }

    public function testGetColonyEntities()
    {
        $objects = $this->_service->getColonyEntities($this->_colonyId);
        $this->assertTrue(!empty($objects));
        $this->assertEquals('Nouron\Model\ResultSet', get_class($objects));
        $this->assertEquals('Techtree\Entity\ColonyPersonell', get_class($objects->current()));
    }

    public function testGetEntities()
    {
        $objects = $this->_service->getEntities();
        $this->assertEquals('Nouron\Model\ResultSet', get_class($objects));
        $this->assertEquals('Techtree\Entity\Personell', get_class($objects->current()));
    }

    public function testGetEntity()
    {
        $object = $this->_service->getEntity($this->_entityId);
        $this->assertEquals('Techtree\Entity\Personell', get_class($object));
        $this->markTestIncomplete();
    }

    public function testGetTotalActionPoints()
    {
        $points = $this->_service->getTotalActionPoints('construction', $this->_colonyId);
        $this->assertTrue($points > PersonellService::DEFAULT_ACTIONPOINTS);
        $points = $this->_service->getTotalActionPoints('research', $this->_colonyId);
        $this->assertTrue($points > PersonellService::DEFAULT_ACTIONPOINTS);
        $points = $this->_service->getTotalActionPoints('navigation', $this->_colonyId);
        $this->assertTrue($points == PersonellService::DEFAULT_ACTIONPOINTS);

    }

    public function testGetConstructionPoints()
    {
        $points = $this->_service->getConstructionPoints($this->_colonyId);
        $this->assertTrue($points > 0);
    }

    public function testGetResearchPoints()
    {
        $points = $this->_service->getResearchPoints($this->_colonyId);
        $this->assertTrue($points > 0);
    }

    public function testGetNavigationPoints()
    {
        $points = $this->_service->getNavigationPoints($this->_colonyId);
        $this->assertTrue($points == 0);
    }

    public function testGetAvailableActionPoints()
    {
        $points = $this->_service->getAvailableActionPoints('construction', $this->_colonyId);
        $this->assertTrue($points > 0);
        $points = $this->_service->getAvailableActionPoints('research', $this->_colonyId);
        $this->assertTrue($points > 0);
        $points = $this->_service->getAvailableActionPoints('navigation', $this->_colonyId);
        $this->assertTrue($points == 0);
    }

    public function testLockActionPoints()
    {
        $this->markTestIncomplete();
    }

    public function testInvest()
    {
        $this->markTestIncomplete();
    }

    public function testHire()
    {
        $this->markTestIncomplete();
    }

    public function testFire()
    {
        $this->markTestIncomplete();
    }

}