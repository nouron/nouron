<?php
namespace TechtreeTest\Service;

use Techtree\Service\Gateway;
use TechtreeTest\Bootstrap;
use PHPUnit_Framework_TestCase;

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
        $tableMocks['technology'] = new \Techtree\Table\Technology($dbAdapter);
        #$tableMocks['resources']  = new \Resources\Table\R($dbAdapter);
        $tableMocks['cost']       = new \Techtree\Table\Cost($dbAdapter);
        $tableMocks['possession'] = $this->getMockBuilder('Techtree\Table\Possession')
                                         ->disableOriginalConstructor()
                                         ->getMock();
        $tableMocks['requirement'] = new \Techtree\Table\Requirement($dbAdapter);

        $tick   = $this->getMockBuilder('Nouron\Service\Tick')
                       ->disableOriginalConstructor()
                       ->getMock();
        $tick->setTickCount(1234);

        $serviceMocks = array();
        $serviceMocks['resources'] = $this->getMockBuilder('Resources\Service\Gateway')
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
        $this->markTestIncomplete();
    }

    public function testGetCosts()
    {
        $objects = $this->_gateway->getCosts();
        $this->assertTrue(!empty($objects));
        $this->assertEquals('Nouron\Model\ResultSet', get_class($objects));
        #$this->markTestIncomplete();
    }

    public function testGetCostsByTechnoloyId()
    {
        $objects = $this->_gateway->getCostsByTechnologyId(25);
        $this->assertTrue(!empty($objects));
        $this->assertEquals('Nouron\Model\ResultSet', get_class($objects));
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
        #$this->markTestIncomplete();
    }

    public function testGetTechnology()
    {
        $object = $this->_gateway->getTechnology(35);
        $this->assertEquals('\Zend\Db\RowGatway\RowGateway', get_class($object));

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