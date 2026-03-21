<?php
namespace INNN\Service;

use PHPUnit\Framework\TestCase;
use INNNTest\Bootstrap;
use INNN\Service\MessageServiceFactory;

class MessageServiceFactoryTest extends TestCase
{
    public function setUp(): void
    {
        $this->sm = Bootstrap::getServiceManager();
        $this->sm->setAllowOverride(true);

        $servicesToMock = array(
            'Laminas\Db\Adapter\Adapter' => 'Laminas\Db\Adapter\Adapter',
            'Core\Service\Tick' => 'Core\Service\Tick',
            'logger' => 'Laminas\Log\Logger',
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
        $entity  = $factory($this->sm, '', []);

        $this->assertInstanceOf(
            "INNN\Service\MessageService",
            $entity
        );
    }
}