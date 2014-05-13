<?php
namespace INNN\Service;

use PHPUnit_Framework_TestCase;
use TechtreeTest\Bootstrap;
use INNN\Service\MessageServiceFactory;

class MessageServiceFactoryTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->sm = Bootstrap::getServiceManager();
        $this->sm->setAllowOverride(true);

        $servicesToMock = array(
            'Zend\Db\Adapter\Adapter' => 'Zend\Db\Adapter\Adapter',
            'Nouron\Service\Tick' => 'Nouron\Service\Tick',
            'logger' => 'Zend\Log\Logger',
            'INNN\Table\MessageTable' => 'INNN\Table\MessageTable',
            'INNN\Table\MessageView' => 'INNN\Table\MessageView',
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
        $factory = new MessageServiceFactory();
        $entity  = $factory->createService($this->sm);

        $this->assertInstanceOf(
            "INNN\Service\MessageService",
            $entity
        );
    }
}