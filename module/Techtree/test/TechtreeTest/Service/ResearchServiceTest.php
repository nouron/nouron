<?php
namespace TechtreeTest\Service;

use PHPUnit_Framework_TestCase;
use NouronTest\Service\AbstractServiceTest;
use Techtree\Service\ResearchService;
use Techtree\Table\ResearchTable;
use Techtree\Table\ResearchCostTable;
use Techtree\Table\ColonyResearchTable;
use Techtree\Entity\Research;
use Techtree\Entity\ResearchCost;
use Techtree\Entity\ColonyResearch;

class ResearchServiceTest extends AbstractServiceTest
{
    public function setUp()
    {
        $this->initDatabaseAdapter();

        $tableMocks = array();
        $tableMocks['researches'] = new ResearchTable($this->dbAdapter, new Research());
        $tableMocks['research_costs']   = new ResearchCostTable($this->dbAdapter, new ResearchCost());
        $tableMocks['colony_researches'] = new ColonyResearchTable($this->dbAdapter, new ColonyResearch());

        $tick = new \Nouron\Service\Tick(1234);
        #$tick->setTickCount(1234);

        $serviceMocks = array();
        $serviceMocks['resources'] = $this->getMockBuilder('Resources\Service\ResourcesService')
                                          ->disableOriginalConstructor()
                                          ->getMock();
        $serviceMocks['galaxy']    = $this->getMockBuilder('Galaxy\Service\Gateway')
                                          ->disableOriginalConstructor()
                                          ->getMock();

        $this->_service = new ResearchService($tick, $tableMocks, $serviceMocks);

        // default test parameters
        $this->_entityId = 74;
        $this->_colonyId = 1;
    }

    public function testCheckRequiredActionPoints()
    {
        $result = $this->_service->checkRequiredActionPoints($this->_colonyId, $this->_entityId);
        $this->assertFalse($result);
        // TODO: check a positive case
        $this->markTestIncomplete();
    }

    public function testGetEntityCosts()
    {
        $objects = $this->_service->getEntityCosts($this->_entityId);
        $this->assertTrue(!empty($objects));
        $this->assertEquals('Nouron\Model\ResultSet', get_class($objects));
        $entityCost = $objects->current();
        $this->assertEquals('Techtree\Entity\ResearchCost', get_class($entityCost));
        $this->assertEquals(
            array(74,1,5000),
            array(
                $entityCost->getResearchId(),
                $entityCost->getResourceId(),
                $entityCost->getAmount()
            )
        );
    }

    public function testColonyEntity()
    {
        $possess = $this->_service->getColonyEntity($this->_colonyId, $this->_entityId);
        $this->assertEquals(17, $possess->getLevel());
    }

    public function testGetColonyEntities()
    {
        $objects = $this->_service->getColonyEntities($this->_colonyId);
        $this->assertTrue(!empty($objects));
        $this->assertEquals('Nouron\Model\ResultSet', get_class($objects));
        $this->assertEquals('Techtree\Entity\ColonyResearch', get_class($objects->current()));
    }

    public function testGetEntities()
    {
        $objects = $this->_service->getEntities();
        $this->assertEquals('Nouron\Model\ResultSet', get_class($objects));
        $this->assertEquals('Techtree\Entity\Research', get_class($objects->current()));
    }

    public function testGetEntity()
    {
        $object = $this->_service->getEntity($this->_entityId);
        $this->assertEquals('Techtree\Entity\Research', get_class($object));
        $this->markTestIncomplete();
    }

    public function testLevelUp()
    {
        // TODO: test successfull level up
        // TODO: test failed checks for level up
        // TODO: test error case
        $this->markTestIncomplete();
    }

    public function testLevelDown()
    {
        // TODO: test successfull level down
        // TODO: test failed checks for level down
        // TODO: test error case
        $this->markTestIncomplete();
    }

}