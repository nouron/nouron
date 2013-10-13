<?php
namespace TechtreeTest\Service;

use Techtree\Service\BuildingService;
use TechtreeTest\Bootstrap;
use PHPUnit_Framework_TestCase;
use Techtree\Table\TechnologyTable;
use Techtree\Table\CostTable;
use Techtree\Table\RequirementTable;
use Techtree\Table\PossessionTable;
use Techtree\Table\ActionPointTable;
use Techtree\Entity\Technology;
use Techtree\Entity\Cost;
use Techtree\Entity\Requirement;
use Techtree\Entity\Possession;
use Techtree\Entity\ActionPoint;

class GatewayTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $dbAdapter = new \Zend\Db\Adapter\Adapter(
            array(
                'driver' => 'Pdo_Mysql',
                'database' => 'nouronzf2_dev',
                'username' => 'root',
                'password' => '',
                'hostname' => 'localhost'
            )
        );

        $tableMocks = array();
        $tableMocks['technology'] = new TechnologyTable($dbAdapter, new Technology());
        #$tableMocks['resources']  = new \Resources\Table\R($dbAdapter);
        $tableMocks['cost']       = new CostTable($dbAdapter, new Cost());
        $tableMocks['possession'] = new PossessionTable($dbAdapter, new Possession());
                                    #$this->getMockBuilder('Techtree\Table\PossessionTable')
                                    #     ->disableOriginalConstructor()
                                    #     ->getMock();
        $tableMocks['requirement'] = new \Techtree\Table\RequirementTable($dbAdapter, new Requirement());
        $tableMocks['locked_actionpoints']  = new ActionPointTable($dbAdapter, new ActionPoint());

        $tick = new \Nouron\Service\Tick(1234);
            # = $this->getMockBuilder('Nouron\Service\Tick')
            #        ->disableOriginalConstructor()
            #        ->getMock();
        #$tick->setTickCount(1234);

        $serviceMocks = array();
        $serviceMocks['resources'] = $this->getMockBuilder('Resources\Service\ResourcesService')
                                          ->disableOriginalConstructor()
                                          ->getMock();
        $serviceMocks['galaxy']    = $this->getMockBuilder('Galaxy\Service\Gateway')
                                          ->disableOriginalConstructor()
                                          ->getMock();

        $this->_gateway = new Gateway($tick, $tableMocks, $serviceMocks);
    }

    public function testGatewayInitialState()
    {
        $this->markTestSkipped();
    }

    public function testGetAvailableActionPoints()
    {
        $colonyId = 0;
        $tmp = $this->_gateway->getAvailableActionPoints('construction', $colonyId);
        $this->assertTrue(is_numeric($tmp) && $tmp > 1);
        $tmp = $this->_gateway->getAvailableActionPoints('research', $colonyId);
        $this->assertTrue(is_numeric($tmp) && $tmp > 1);
        $this->markTestIncomplete();
    }

    public function testGetCosts()
    {
        $objects = $this->_gateway->getCosts();
        $this->assertTrue(!empty($objects));
        $this->assertEquals('Nouron\Model\ResultSet', get_class($objects));
        $this->assertEquals('Techtree\Entity\Cost', get_class($objects->current()));
        #$this->markTestIncomplete();
    }

    public function testGetCostsByTechnoloyId()
    {
        $objects = $this->_gateway->getCostsByTechnologyId(25);
        $this->assertTrue(!empty($objects));
        $this->assertEquals('Nouron\Model\ResultSet', get_class($objects));
        $this->assertEquals('Techtree\Entity\Cost', get_class($objects->current()));
        #$this->markTestIncomplete();
    }

    public function testGetLevelByTechnologyId()
    {
        $this->markTestIncomplete();
    }

    public function testGetPossessionByTechnologyId()
    {
        #$this->_gateway->getPossessionByTechnologyId(25, 0);
        $this->markTestIncomplete();
    }

    public function testGetPossessionsByColonyId()
    {
        $this->markTestIncomplete();
    }

    public function testGetPossessionByUserId()
    {
        $this->markTestIncomplete();
    }

    public function testGetRequirements()
    {
        $objects = $this->_gateway->getRequirements();
        $this->assertTrue(!empty($objects));
        $this->assertEquals('Nouron\Model\ResultSet', get_class($objects));
        $this->assertEquals('Techtree\Entity\Requirement', get_class($objects->current()));
        #$this->markTestIncomplete();
    }

    public function testGetRequirementsAsArray()
    {
        $this->markTestIncomplete();
    }

    public function testGetRequirementsByTechnologyId()
    {
        $this->markTestIncomplete();
    }

    public function testGetTechnologies()
    {
        $objects = $this->_gateway->getTechnologies();
        $this->assertEquals('Nouron\Model\ResultSet', get_class($objects));
        $this->assertEquals('Techtree\Entity\Technology', get_class($objects->current()));
        #$this->markTestIncomplete();
    }

    public function testGetTechnology()
    {
        $object = $this->_gateway->getTechnology(35);
        $this->assertEquals('Techtree\Entity\Technology', get_class($object));

        $this->markTestIncomplete();
    }

    public function testGetTechtreeByColonyId()
    {
        $this->markTestIncomplete();
    }

    public function getTotalActionPoints()
    {
        $this->markTestIncomplete();
    }

    public function testOrder()
    {
        $this->markTestIncomplete();
    }

}