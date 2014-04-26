<?php
namespace TradeTest\Service;

use NouronTest\Service\AbstractServiceTest;
use Trade\Service\Gateway;
use Trade\Table\ResearchTable;
use Trade\Table\ResearchView;
use Trade\Table\ResourceTable;
use Trade\Table\ResourceView;
use Trade\Entity\Research;
use Trade\Entity\Resource;

class GatewayTest extends AbstractServiceTest
{
    public function setUp()
    {
        $this->initDatabaseAdapter();

        $tables = array();
        $tables['researches']      = new ResearchTable($this->dbAdapter, new Research());
        $tables['researches_view'] = new ResearchView($this->dbAdapter, new Research());
        $tables['resources']       = new ResourceTable($this->dbAdapter, new Resource());
        $tables['resources_view']  = new ResourceView($this->dbAdapter, new Resource());

        $tick = new \Nouron\Service\Tick(1234);
        #$tick->setTickCount(1234);

        $serviceMocks = array();
        $serviceMocks['resources'] = $this->getMockBuilder('Resources\Service\ResourcesService')
                                          ->disableOriginalConstructor()
                                          ->getMock();

        $paramsMap = array(
            array(1,99, false),
            array(1,3, true)
        );
        $galaxyService = $this->getMockBuilder('Galaxy\Service\Gateway')
                              ->disableOriginalConstructor()
                              ->getMock();
        $galaxyService->expects($this->any())
                      ->method('checkColonyOwner')
                      ->will($this->returnValueMap($paramsMap));
        $serviceMocks['galaxy'] = $galaxyService;

        $this->_gateway = new Gateway($tick, $tables, $serviceMocks);
        $logger = $this->getMockBuilder('Zend\Log\Logger')
                       ->disableOriginalConstructor()
                       ->getMock();
        $this->_gateway->setLogger($logger);

    }

    public function testGatewayInitialState()
    {
        $tableMocks = array();
        $tableMocks['researches'] = $this->getMockBuilder('Trade\Table\Research')
                                         ->disableOriginalConstructor()
                                         ->getMock();
        $tableMocks['researches_view'] = $this->getMockBuilder('Trade\Table\ResearchView')
                                         ->disableOriginalConstructor()
                                         ->getMock();
        $tableMocks['resources']  = $this->getMockBuilder('Trade\Table\Resource')
                                         ->disableOriginalConstructor()
                                         ->getMock();
        $tableMocks['resources_view']  = $this->getMockBuilder('Trade\Table\ResourceView')
                                         ->disableOriginalConstructor()
                                         ->getMock();

        $tick   = $this->getMockBuilder('Nouron\Service\Tick')
                       ->disableOriginalConstructor()
                       ->getMock();
        $tick->setTickCount(1234);

        $serviceMocks = array();
        $serviceMocks['resources'] = $this->getMockBuilder('Resources\Service\ResourcesService')
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
        $objects = $this->_gateway->getResearches();
        $objects = $this->_gateway->getResources();
    }

    public function testAddResourceOffer()
    {
        $data = array(
            'colony_id' => 1,
            'direction' => 1,
            'resource_id' => 3,
            'amount' => 100,
            'price' => 50,
            'restriction' => 0,
        );
        $result = $this->_gateway->addResourceOffer($data);
        $this->assertFalse($result); // missing user id

        $data['user_id'] = 99;
        $result = $this->_gateway->addResourceOffer($data);
        $this->assertFalse($result); // user is not owner of colony

        $data['user_id'] = 3;
        $result = $this->_gateway->addResourceOffer($data);
        $this->assertTrue($result);

    }

    public function testAddResearchOffer()
    {
        $data = array(
            'colony_id' => 1,
            'direction' => 1,
            'research_id' => 27,
            'amount' => 2,
            'price' => 50,
            'restriction' => 0,
        );
        $result = $this->_gateway->addResearchOffer($data);
        $this->assertFalse($result); // missing user id

        $data['user_id'] = 99;
        $result = $this->_gateway->addResearchOffer($data);
        $this->assertFalse($result); // user is not owner of colony

        $data['user_id'] = 3;
        $result = $this->_gateway->addResearchOffer($data);
        $this->assertTrue($result);

    }

    public function testRemoveResourceOffer()
    {
        $this->initDatabase();
        $data = array(
            'colony_id' => 1,
            'resource_id' => 8,
            'direction' => 0
        );

        $result = $this->_gateway->removeResourceOffer($data);
        $this->assertFalse($result); // missing user id

        $data['user_id'] = 99;
        $result = $this->_gateway->removeResourceOffer($data);
        $this->assertFalse($result); // user is not owner of colony

        $data['user_id'] = 3;
        $result = $this->_gateway->removeResourceOffer($data);
        $this->assertTrue($result);
    }

    public function testRemoveResearchOffer()
    {
        $this->initDatabase();
        $data = array(
            'colony_id' => 1,
            'research_id' => 35,
            'direction' => 0
        );

        $result = $this->_gateway->removeResearchOffer($data);
        $this->assertFalse($result); // missing user id

        $data['user_id'] = 99;
        $result = $this->_gateway->removeResearchOffer($data);
        $this->assertFalse($result); // user is not owner of colony

        $data['user_id'] = 3;
        $result = $this->_gateway->removeResearchOffer($data);
        $this->assertTrue($result);
    }
}