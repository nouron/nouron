<?php
namespace INNN\Service;

use PHPUnit_Framework_TestCase;
use INNNTest\Bootstrap;
use INNN\Service\EventServiceFactory;

class EventServiceFactoryTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->sm = Bootstrap::getServiceManager();
        $this->sm->setAllowOverride(true);

        $servicesToMock = array(
            'Zend\Db\Adapter\Adapter' => 'Zend\Db\Adapter\Adapter',
            'Core\Service\Tick' => 'Core\Service\Tick',
            'logger' => 'Zend\Log\Logger',
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
        $entity  = $factory->createService($this->sm);

        $this->assertInstanceOf(
            "INNN\Service\EventService",
            $entity
        );
    }
}