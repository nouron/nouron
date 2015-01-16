<?php
namespace ColonyTest\Service;

use PHPUnit_Framework_TestCase;
use ColonyTest\Bootstrap;
use Colony\Service\ColonyServiceFactory;

class ColonyServiceFactoryTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->sm = Bootstrap::getServiceManager();
        $this->sm->setAllowOverride(true);

        $servicesToMock = array(
            'Zend\Db\Adapter\Adapter' => 'Zend\Db\Adapter\Adapter',
            'Core\Service\Tick' => 'Core\Service\Tick',
            'logger' => 'Zend\Log\Logger',
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
        $entity  = $factory->createService($this->sm);

        $this->assertInstanceOf(
            "Colony\Service\ColonyService",
            $entity
        );
    }
}