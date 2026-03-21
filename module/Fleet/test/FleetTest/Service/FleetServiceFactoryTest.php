<?php
namespace FleetTest\Service;

use PHPUnit\Framework\TestCase;
use FleetTest\Bootstrap;
use Fleet\Service\FleetServiceFactory;

class FleetServiceFactoryTest extends TestCase
{
    public function setUp(): void
    {
        $this->sm = Bootstrap::getServiceManager();
        $this->sm->setAllowOverride(true);

        $servicesToMock = array(
            'Laminas\Db\Adapter\Adapter' => 'Laminas\Db\Adapter\Adapter',
            'Core\Service\Tick' => 'Core\Service\Tick',
            'logger' => 'Laminas\Log\Logger',
            'Colony\Table\ColonyTable' => 'Colony\Table\ColonyTable',
            'Galaxy\Table\SystemTable' => 'Galaxy\Table\SystemTable',
            'Fleet\Table\FleetTable' => 'Fleet\Table\FleetTable',
            'Fleet\Table\FleetTable' => 'Fleet\Table\FleetTable',
            'Fleet\Table\FleetShipTable' => 'Fleet\Table\FleetShipTable',
            'Fleet\Table\FleetPersonellTable' => 'Fleet\Table\FleetPersonellTable',
            'Fleet\Table\FleetResearchTable' => 'Fleet\Table\FleetResearchTable',
            'Fleet\Table\FleetOrderTable' => 'Fleet\Table\FleetOrderTable',
            'Fleet\Table\FleetResourceTable' => 'Fleet\Table\FleetResourceTable',

            'Techtree\Table\ShipTable' =>  'Techtree\Table\ShipTable',
            'Techtree\Table\PersonellTable' =>  'Techtree\Table\PersonellTable',
            'Techtree\Table\ResearchTable' =>  'Techtree\Table\ResearchTable',

            'Techtree\Table\ColonyShipTable' => 'Techtree\Table\ColonyShipTable',
            'Techtree\Table\ColonyPersonellTable' => 'Techtree\Table\ColonyPersonellTable',
            'Techtree\Table\ColonyResearchTable' => 'Techtree\Table\ColonyResearchTable',
            'Resources\Table\ColonyTable' => 'Resources\Table\ColonyTable'
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
        $factory = new FleetServiceFactory();
        $entity  = $factory($this->sm, '', []);

        $this->assertInstanceOf(
            "Fleet\Service\FleetService",
            $entity
        );
    }
}