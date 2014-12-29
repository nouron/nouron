<?php
namespace TradeTest\Service;

use CoreTest\Service\AbstractServiceTest;
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

        $tick = new \Core\Service\Tick(16000);

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
        $this->assertInstanceOf('Trade\Service\Gateway', $this->_gateway);
        $this->assertInstanceOf('Trade\Table\ResearchTable', $this->_gateway->getTable('researches'));
        $this->assertInstanceOf('Trade\Table\ResearchView', $this->_gateway->getTable('researches_view'));
        $this->assertInstanceOf('Trade\Table\ResourceTable', $this->_gateway->getTable('resources'));
        $this->assertInstanceOf('Trade\Table\ResourceView', $this->_gateway->getTable('resources_view'));
        #$this->assertEquals('Resource\Service\ResourcesService', get_class($this->_gateway->getService('resources')));
        #$this->assertEquals('Galaxy\Service\Gateway', get_class($this->_gateway->getTable('galaxy')));
        $this->assertEquals('integer', gettype($this->_gateway->getTick()));
    }

    public function testGetObjects()
    {
        $offers = $this->_gateway->getResources();
        $this->assertInstanceOf('Core\Model\ResultSet', $offers);
        $this->assertInstanceOf('Trade\Entity\Resource', $offers->current());

        $offers = $this->_gateway->getResearches();
        $this->assertInstanceOf('Core\Model\ResultSet', $offers);
        $this->assertInstanceOf('Trade\Entity\Research', $offers->current());

        #this->markTestIncomplete();
    }

    public function testAddResourceOffer()
    {
        $this->initDatabase();
        $data = array(
            'colony_id' => 1,
            'direction' => 1,
            'resource_id' => 3
        );

        // will fail because of missing user id
        $result = $this->_gateway->addResourceOffer($data);
        $this->assertFalse($result);

        // will fail because of user is not owner of colony
        $data['user_id'] = 99;
        $result = $this->_gateway->addResourceOffer($data);
        $this->assertFalse($result);

        // change to real owner
        $data['user_id'] = 3;

        // resource offer doesn't exist, add new resource offer
        $offers = $this->_gateway->getResources($data);
        $this->assertEquals('Core\Model\ResultSet', get_class($offers));
        $this->assertEquals(0, $offers->count());
        $dataToAdd = $data + array(
            'amount' => 100,
            'price' => 50,
            'restriction' => 0
        );
        $result = $this->_gateway->addResourceOffer($dataToAdd);
        $this->assertTrue($result);
        $offers = $this->_gateway->getResources($data);
        $this->assertEquals('Core\Model\ResultSet', get_class($offers));
        $this->assertEquals(1, $offers->count());
        $this->assertEquals(100, $offers->current()->getAmount());

        // resource offer exists, update this offer with new amount
        $dataToAdd['amount'] = 500;
        $result = $this->_gateway->addResourceOffer($dataToAdd);
        $this->assertTrue($result);
        $offers = $this->_gateway->getResources($data);
        $this->assertEquals('Core\Model\ResultSet', get_class($offers));
        $this->assertEquals(1, $offers->count());
        $this->assertEquals(500, $offers->current()->getAmount());
    }

    public function testAddResearchOffer()
    {
        $this->initDatabase();
        $data = array(
            'colony_id' => 1,
            'direction' => 1,
            'research_id' => 27
        );

        // will fail because of missing user id
        $result = $this->_gateway->addResearchOffer($data);
        $this->assertFalse($result);

        // will fail because of user is not owner of colony
        $data['user_id'] = 99;
        $result = $this->_gateway->addResearchOffer($data);
        $this->assertFalse($result);

        // change to real owner
        $data['user_id'] = 3;

        // resource offer doesn't exist, add new resource offer
        $offers = $this->_gateway->getResearches($data);
        $this->assertInstanceOf('Core\Model\ResultSet', $offers);
        $this->assertEquals(0, $offers->count());
        $dataToAdd = $data + array(
            'amount' => 2,
            'price' => 50,
            'restriction' => 0
        );
        $result = $this->_gateway->addResearchOffer($dataToAdd);
        $this->assertTrue($result);
        $offers = $this->_gateway->getResearches($data);
        $this->assertInstanceOf('Core\Model\ResultSet', $offers);
        $this->assertEquals(1, $offers->count());
        $this->assertEquals(2, $offers->current()->getAmount());

        // resource offer exists, update this offer with new amount
        $dataToAdd['amount'] = 5;
        $result = $this->_gateway->addResearchOffer($dataToAdd);
        $this->assertTrue($result);
        $offers = $this->_gateway->getResearches($data);
        $this->assertInstanceOf('Core\Model\ResultSet', $offers);
        $this->assertEquals(1, $offers->count());
        $this->assertEquals(5, $offers->current()->getAmount());
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
        $offers = $this->_gateway->getResources($data);
        $this->assertEquals(1, $offers->count());
        $result = $this->_gateway->removeResourceOffer($data);
        $this->assertTrue($result);
        $offers = $this->_gateway->getResources($data);
        $this->assertEquals(0, $offers->count());
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
        $offers = $this->_gateway->getResearches($data);
        $this->assertEquals(1, $offers->count());
        $result = $this->_gateway->removeResearchOffer($data);
        $this->assertTrue($result);
        $offers = $this->_gateway->getResearches($data);
        $this->assertEquals(0, $offers->count());
    }
}