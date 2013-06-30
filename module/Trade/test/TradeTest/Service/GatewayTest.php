<?php
namespace TradeTest\Model;

use Trade\Service\Gateway;
use PHPUnit_Framework_TestCase;

class GatewayTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $tableMocks = array();
        $tableMocks['technology'] = $this->getMockBuilder('Trade\Table\Technology')
                                         ->disableOriginalConstructor()
                                         ->getMock();
        $tableMocks['resources']  = $this->getMockBuilder('Trade\Table\Resource')
                                         ->disableOriginalConstructor()
                                         ->getMock();

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
        $tableMocks = array();
        $tableMocks['technology'] = $this->getMockBuilder('Trade\Table\Technology')
                                         ->disableOriginalConstructor()
                                         ->getMock();
        $tableMocks['resources']  = $this->getMockBuilder('Trade\Table\Resource')
                                         ->disableOriginalConstructor()
                                         ->getMock();

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

        $gw = new Gateway($tick, $tableMocks, $serviceMocks);

        $this->assertEquals('Trade\Service\Gateway', get_class($gw));

        $this->markTestIncomplete();

    }

    public function testGetObjects()
    {
        $objects = $this->_gateway->getTechnologies();
        $objects = $this->_gateway->getResources();
    }

    public function testAddOffer()
    {
        # alter mocks
        $galaxyService = $this->_gateway->getService( 'galaxy');
        $galaxyService->expects($this->once())
                      ->method('checkColonyOwner')
                      ->will($this->returnValue(true));
        $this->_gateway->setService('galaxy', $galaxyService);

        $resourcesTable = $this->_gateway->getTable('resources');
        $resourcesTable->expects($this->once())
                      ->method('getEntity')
                      ->will($this->returnValue(array(
                            'colony_id' => 0,
                            'direction' => 1,
                            'resource_id' => 3
                      )));
        $resourcesTable->expects($this->once())
                      ->method('save')
                      ->will($this->returnValue(true));
        $this->_gateway->setTable('resources', $resourcesTable);
        # end of alter mocks

        $data = array(
            'item_type'=>'resource',
            'item_id' => 3,
            'colony_id' => 0,
            'direction' => 1,
            'amount' => 100,
            'price' => 50,
            'restriction' => 0,
        );
        $result = $this->_gateway->addOffer($data);
        $this->assertTrue($result);
    }

    public function testRemoveOffer()
    {
        $data = array();
        $this->_gateway->removeOffer($data);
    }
}