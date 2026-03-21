<?php
namespace GalaxyTest\Service;

use PHPUnit\Framework\TestCase;
use GalaxyTest\Bootstrap;
use Galaxy\Service\GatewayFactory;

class GatewayFactoryTest extends TestCase
{
    public function setUp(): void
    {
        $this->sm = Bootstrap::getServiceManager();
        $this->sm->setAllowOverride(true);

        $servicesToMock = array(
            'Laminas\Db\Adapter\Adapter' => 'Laminas\Db\Adapter\Adapter',
            'Core\Service\Tick' => 'Core\Service\Tick',
            'logger' => 'Laminas\Log\Logger',
            'Galaxy\Table\SystemTable'       => 'Galaxy\Table\SystemTable',
            'Galaxy\Table\SystemObjectTable' => 'Galaxy\Table\SystemObjectTable',
            #'Galaxy\Table\FleetTable'        => 'Galaxy\Table\FleetTable',

            'Colony\Table\ColonyTable'       => 'Colony\Table\ColonyTable',
            'Fleet\Table\FleetTable'         => 'Fleet\Table\FleetTable',
            'Fleet\Table\FleetShipTable'     => 'Fleet\Table\FleetShipTable',
            'Fleet\Table\FleetPersonellTable' => 'Fleet\Table\FleetPersonellTable',
            'Fleet\Table\FleetResearchTable'  => 'Fleet\Table\FleetResearchTable',
            'Fleet\Table\FleetOrderTable'     => 'Fleet\Table\FleetOrderTable',
            'Fleet\Table\FleetResourceTable'  => 'Fleet\Table\FleetResourceTable',
            'Techtree\Table\ColonyBuildingTable' => 'Techtree\Table\ColonyBuildingTable',
            'Resources\Table\ColonyTable' => 'Resources\Table\ColonyTable',
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
        $entity  = $factory($this->sm, '', []);

        $this->assertInstanceOf(
            "Galaxy\Service\Gateway",
            $entity
        );
    }
}