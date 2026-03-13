<?php
namespace TechtreeTest\Service;

use PHPUnit\Framework\TestCase;
use TechtreeTest\Bootstrap;
use Techtree\Service\BuildingServiceFactory;

class BuildingServiceFactoryTest extends TestCase
{
    public function setUp(): void
    {
        $this->sm = Bootstrap::getServiceManager();
        $this->sm->setAllowOverride(true);

        $servicesToMock = array(
            'Laminas\Db\Adapter\Adapter' => 'Laminas\Db\Adapter\Adapter',
            'Core\Service\Tick' => 'Core\Service\Tick',
            'logger' => 'Laminas\Log\Logger',
            'Techtree\Table\BuildingTable' => 'Techtree\Table\BuildingTable',
            'Techtree\Table\BuildingCostTable' => 'Techtree\Table\BuildingCostTable',
            'Techtree\Table\ColonyBuildingTable' => 'Techtree\Table\ColonyBuildingTable',
            'Techtree\Table\ColonyPersonellTable' => 'Techtree\Table\ColonyPersonellTable',
            'Techtree\Table\ActionPointTable' => 'Techtree\Table\ActionPointTable',
            'Techtree\Table\ColonyTable' => 'Techtree\Table\ColonyTable',
            'Techtree\Table\PersonellTable' => 'Techtree\Table\PersonellTable',
            'Techtree\Table\PersonellCostTable' => 'Techtree\Table\PersonellCostTable',
            'Colony\Table\ColonyTable' => 'Colony\Table\ColonyTable',
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
        $factory = new BuildingServiceFactory();
        $entity  = $factory($this->sm, '', []);

        $this->assertInstanceOf(
            "Techtree\Service\BuildingService",
            $entity
        );
    }
}