<?php
namespace ColonyTest\Service;

use PHPUnit\Framework\TestCase;
use ColonyTest\Bootstrap;
use Colony\Service\ColonyServiceFactory;

class ColonyServiceFactoryTest extends TestCase
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
            'Galaxy\Table\SystemObjectTable' => 'Galaxy\Table\SystemObjectTable',
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
        $factory = new ColonyServiceFactory();
        $entity  = $factory($this->sm, '', []);

        $this->assertInstanceOf(
            "Colony\Service\ColonyService",
            $entity
        );
    }
}