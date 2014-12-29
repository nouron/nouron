<?php
namespace FleetTest\Service;

use PHPUnit_Framework_TestCase;
use TechtreeTest\Bootstrap;
use Fleet\Service\FleetServiceFactory;

class FleetServiceFactoryTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->sm = Bootstrap::getServiceManager();
        $this->sm->setAllowOverride(true);

        $servicesToMock = array(
            'Zend\Db\Adapter\Adapter' => 'Zend\Db\Adapter\Adapter',
            'Core\Service\Tick' => 'Core\Service\Tick',
            'logger' => 'Zend\Log\Logger',
            'Galaxy\Table\ColonyTable' => 'Galaxy\Table\ColonyTable',
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
        $entity  = $factory->createService($this->sm);

        $this->assertInstanceOf(
            "Fleet\Service\FleetService",
            $entity
        );
    }
}