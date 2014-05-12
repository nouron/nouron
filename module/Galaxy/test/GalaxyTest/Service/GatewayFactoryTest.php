<?php
namespace GalaxyTest\Service;

use PHPUnit_Framework_TestCase;
use TechtreeTest\Bootstrap;
use Galaxy\Service\GatewayFactory;

class GatewayFactoryTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->sm = Bootstrap::getServiceManager();
        $this->sm->setAllowOverride(true);

        $servicesToMock = array(
            'Zend\Db\Adapter\Adapter' => 'Zend\Db\Adapter\Adapter',
            'Nouron\Service\Tick' => 'Nouron\Service\Tick',
            'logger' => 'Zend\Log\Logger',
            'Galaxy\Table\ColonyTable'         => 'Galaxy\Table\ColonyTable',
            'Galaxy\Table\SystemTable'         => 'Galaxy\Table\SystemTable',
            'Galaxy\Table\FleetTable'          => 'Galaxy\Table\FleetTable',
            'Galaxy\Table\SystemObjectTable'   => 'Galaxy\Table\SystemObjectTable',
            'Fleet\Table\FleetShipTable'      => 'Fleet\Table\FleetShipTable',
            'Fleet\Table\FleetPersonellTable' => 'Fleet\Table\FleetPersonellTable',
            'Fleet\Table\FleetResearchTable'  => 'Fleet\Table\FleetResearchTable',
            'Fleet\Table\FleetOrderTable'     => 'Fleet\Table\FleetOrderTable',
            'Fleet\Table\FleetResourceTable'  => 'Fleet\Table\FleetResourceTable',
            'Techtree\Table\ColonyBuildingTable' => 'Techtree\Entity\ColonyBuildingTable',
            'Resources\Table\ColonyTable' => 'Resources\Entity\ColonyTable',
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
            "Galaxy\Service\Gateway",
            $entity
        );
    }
}