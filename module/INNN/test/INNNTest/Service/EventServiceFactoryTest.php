<?php
namespace INNN\Service;

use PHPUnit\Framework\TestCase;
use INNNTest\Bootstrap;
use INNN\Service\EventServiceFactory;

class EventServiceFactoryTest extends TestCase
{
    public function setUp(): void
    {
        $this->sm = Bootstrap::getServiceManager();
        $this->sm->setAllowOverride(true);

        $servicesToMock = array(
            'Laminas\Db\Adapter\Adapter' => 'Laminas\Db\Adapter\Adapter',
            'Core\Service\Tick' => 'Core\Service\Tick',
            'logger' => 'Laminas\Log\Logger',
            'INNN\Table\EventTable' => 'INNN\Table\EventTable',
            'INNN\Table\EventView' => 'INNN\Table\EventView',
            'User\Table\UserTable' => 'User\Table\UserTable',
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
        $factory = new EventServiceFactory();
        $entity  = $factory($this->sm, '', []);

        $this->assertInstanceOf(
            "INNN\Service\EventService",
            $entity
        );
    }
}