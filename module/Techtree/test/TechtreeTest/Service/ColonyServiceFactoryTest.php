<?php
namespace TechtreeTest\Service;

use PHPUnit_Framework_TestCase;
use TechtreeTest\Bootstrap;
use Techtree\Service\ColonyServiceFactory;

class ColonyServiceFactoryTest extends PHPUnit_Framework_TestCase
{
    /**
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @return \Techtree\Service\BuildingService
     */
    public function setUp()
    {
        $this->sm = Bootstrap::getServiceManager();
        $this->sm->setAllowOverride(true);

        $servicesToMock = array(
            'Core\Service\Tick' => 'Core\Service\Tick',
            'logger' => 'Zend\Log\Logger',
            'Techtree\Table\BuildingTable' => 'Techtree\Table\BuildingTable',
            'Techtree\Table\BuildingCostTable' => 'Techtree\Table\BuildingCostTable',
            'Techtree\Table\ColonyBuildingTable' => 'Techtree\Table\ColonyBuildingTable',
            'Techtree\Table\ColonyResearchTable' => 'Techtree\Table\ColonyResearchTable',
            'Techtree\Table\ColonyShipTable' => 'Techtree\Table\ColonyShipTable',
            'Techtree\Table\ColonyPersonellTable' => 'Techtree\Table\ColonyPersonellTable',
            'Techtree\Table\ColonyTable' => 'Techtree\Table\ColonyTable',
            'Techtree\Table\ResearchTable' => 'Techtree\Table\ResearchTable',
            'Techtree\Table\ShipTable' => 'Techtree\Table\ShipTable',
            'Techtree\Table\PersonellTable' => 'Techtree\Table\PersonellTable',
            'Resources\Service\ResourcesService' => 'Resources\Service\ResourcesService'
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
        $factory = new ColonyServiceFactory();
        $entity  = $factory->createService($this->sm);

        $this->assertInstanceOf(
            "Techtree\Service\ColonyService",
            $entity
        );
    }
}