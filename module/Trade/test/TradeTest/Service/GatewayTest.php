<?php
namespace TradeTest\Service;

use PHPUnit_Framework_TestCase;
use Trade\Service\Gateway;
use Trade\Table\ResearchTable;
use Trade\Table\ResearchView;
use Trade\Table\ResourceTable;
use Trade\Table\ResourceView;
use Trade\Entity\Research;
use Trade\Entity\Resource;

class GatewayTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $basePath = __DIR__ . '/../../../../../';

        $rr = exec("sqlite3 " . $basePath . "data/db/test.db < " . $basePath . "data/sql/drop_all.sql");
        #$rr = exec("sqlite3 ../../../data/db/test.db < ../../../sql/truncate_all.sql");
        $rr = exec("sqlite3 " . $basePath . "data/db/test.db < " . $basePath . "data/dump");

        $dbAdapter = new \Zend\Db\Adapter\Adapter(
            array(
                'driver' => 'Pdo_Sqlite',
                'database' => '../data/db/test.db'
            )
        );

        $tables = array();
        $tables['researches']      = new ResearchTable($dbAdapter, new Research());
        $tables['researches_view'] = new ResearchView($dbAdapter, new Research());
        $tables['resources']       = new ResourceTable($dbAdapter, new Resource());
        $tables['resources_view']  = new ResourceView($dbAdapter, new Resource());

        $tick = new \Nouron\Service\Tick(1234);
        #$tick->setTickCount(1234);

        $serviceMocks = array();
        $serviceMocks['resources'] = $this->getMockBuilder('Resources\Service\ResourcesService')
                                          ->disableOriginalConstructor()
                                          ->getMock();

        $serviceMocks['galaxy']    = $this->getMockBuilder('Galaxy\Service\Gateway')
                                          ->disableOriginalConstructor()
                                          ->getMock();

#        $tableMocks = array();
#        $tableMocks['researches'] = $this->getMockBuilder('Trade\Table\Research')
#                                         ->disableOriginalConstructor()
#                                         ->getMock();
#        $tableMocks['resources']  = $this->getMockBuilder('Trade\Table\Resource')
#                                         ->disableOriginalConstructor()
#                                         ->getMock();
#
#        $tick   = $this->getMockBuilder('Nouron\Service\Tick')
#                       ->disableOriginalConstructor()
#                       ->getMock();
#        $tick->setTickCount(1234);
#
#        $serviceMocks = array();
#        $serviceMocks['resources'] = $this->getMockBuilder('Resources\Service\ResourcesService')
#                                          ->disableOriginalConstructor()
#                                          ->getMock();
#        $serviceMocks['galaxy']    = $this->getMockBuilder('Galaxy\Service\Gateway')
#                                          ->disableOriginalConstructor()
#                                          ->getMock();

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

    public function testAddOffer()
    {
        # alter mocks
        $galaxyService = $this->_gateway->getService( 'galaxy');
        $galaxyService->expects($this->once())
                      ->method('checkColonyOwner')
                      ->will($this->returnValue(true));
        $this->_gateway->setService('galaxy', $galaxyService);

        $resourcesTable = $this->getMockBuilder('Trade\Table\ResourcesTable')
                              ->disableOriginalConstructor()
                              ->getMock();

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
        $this->_gateway->removeResourceOffer($data);
        $this->_gateway->removeResearchOffer($data);
        $this->markTestIncomplete();
    }
}