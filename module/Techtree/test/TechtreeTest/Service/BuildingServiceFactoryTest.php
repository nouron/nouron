<?php
namespace TechtreeTest\Service;

use PHPUnit_Framework_TestCase;
use TechtreeTest\Bootstrap;

class BuildingServiceFactoryTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->sm = Bootstrap::getServiceManager();
        $this->sm->setAllowOverride(true);

        $servicesToMock = array(
            'Nouron\Service\Tick' => 'Nouron\Service\Tick',
            'logger' => 'Zend\Log\Logger',
            'Techtree\Table\BuildingTable' => 'Techtree\Table\BuildingTable',
            'Techtree\Table\BuildingCostTable' => 'Techtree\Table\BuildingCostTable',
            'Techtree\Table\ColonyBuildingTable' => 'Techtree\Table\ColonyBuildingTable',
            'Techtree\Table\ColonyPersonellTable' => 'Techtree\Table\ColonyPersonellTable',
            'Techtree\Table\ActionPointTable' => 'Techtree\Table\ActionPointTable',
            'Techtree\Table\ColonyTable' => 'Techtree\Table\ColonyTable',
            'Techtree\Table\PersonellTable' => 'Techtree\Table\PersonellTable',
            'Techtree\Table\PersonellCostTable' => 'Techtree\Table\PersonellCostTable',
            'Galaxy\Table\ColonyTable' => 'Galaxy\Table\ColonyTable',
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
        $this->assertInstanceOf(
            "Techtree\Service\BuildingService",
            $this->sm->get('Techtree\Service\BuildingService')
        );
    }
}