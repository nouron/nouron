<?php
namespace TradeTest\Service;

use PHPUnit_Framework_TestCase;
use TechtreeTest\Bootstrap;
use Trade\Service\GatewayFactory;

class GatewayFactoryTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->sm = Bootstrap::getServiceManager();
        $this->sm->setAllowOverride(true);

        $servicesToMock = array(
            'Zend\Db\Adapter\Adapter' => 'Zend\Db\Adapter\Adapter',
            'Core\Service\Tick' => 'Core\Service\Tick',
            'logger' => 'Zend\Log\Logger',
            'Trade\Table\ResearchTable' => 'Trade\Table\ResearchTable',
            'Trade\Table\ResearchView' => 'Trade\Table\ResearchView',
            'Trade\Table\ResourceTable' => 'Trade\Table\ResourceTable',
            'Trade\Table\ResourceView' => 'Trade\Table\ResourceView',

            # TODO: check if all these dependencies are really necessary and
            #       get rid of them if not!
            'Galaxy\Table\ColonyTable'       => 'Galaxy\Table\ColonyTable',
            'Galaxy\Table\SystemTable'       => 'Galaxy\Table\SystemTable',
            'Galaxy\Table\SystemObjectTable' => 'Galaxy\Table\SystemObjectTable',
            'Fleet\Table\FleetTable'        => 'Fleet\Table\FleetTable',
            'Techtree\Table\ColonyBuildingTable' => 'Techtree\Table\ColonyBuildingTable',
            'Resources\Table\ColonyTable' => 'Resources\Table\ColonyTable',
            'Resources\Table\ResourceTable' => 'Resources\Table\ResourceTable',
            'Resources\Table\UserTable' => 'Resources\Table\UserTable',
        );
        foreach ($servicesToMock as $key => $serviceName) {
            $this->sm->setService(
                $key,
                $this->getMockBuilder($serviceName)
                     ->disableOriginalConstructor()
                     ->getMock()
            );
        }
    }

    public function testCreateService()
    {
        $factory = new GatewayFactory();
        $entity  = $factory->createService($this->sm);

        $this->assertInstanceOf(
            "Trade\Service\Gateway",
            $entity
        );
    }
}