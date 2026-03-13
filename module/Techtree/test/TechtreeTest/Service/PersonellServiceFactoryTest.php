<?php
namespace TechtreeTest\Service;

use PHPUnit\Framework\TestCase;
use TechtreeTest\Bootstrap;

class PersonellServiceFactoryTest extends TestCase
{
    public function setUp(): void
    {
        $this->sm = Bootstrap::getServiceManager();
        $this->sm->setAllowOverride(true);

        $this->sm->setService(
            'Core\Service\Tick',
            $this->getMockBuilder('Core\Service\Tick')
                  ->disableOriginalConstructor()
                  ->getMock()
        );

        $this->sm->setService(
            'logger',
            $this->getMockBuilder('Laminas\Log\Logger')
                  ->disableOriginalConstructor()
                  ->getMock()
        );

        $this->sm->setService(
            'Techtree\Table\PersonellTable',
            $this->getMockBuilder('Techtree\Table\PersonellTable')
                 ->disableOriginalConstructor()
                 ->getMock()
        );

        $this->sm->setService(
            'Techtree\Table\PersonellCostTable',
            $this->getMockBuilder('Techtree\Table\PersonellCostTable')
                 ->disableOriginalConstructor()
                 ->getMock()
        );

        $this->sm->setService(
            'Techtree\Table\ColonyPersonellTable',
            $this->getMockBuilder('Techtree\Table\ColonyPersonellTable')
                 ->disableOriginalConstructor()
                 ->getMock()
        );

        $this->sm->setService(
            'Techtree\Table\ActionPointTable',
            $this->getMockBuilder('Techtree\Table\ActionPointTable')
                 ->disableOriginalConstructor()
                 ->getMock()
        );

        $this->sm->setService(
            'Resources\Service\ResourcesService',
            $this->getMockBuilder('Resources\Service\ResourcesService')
                 ->disableOriginalConstructor()
                 ->getMock()
        );

        $this->sm->setService(
            'Galaxy\Service\Gateway',
            $this->getMockBuilder('Galaxy\Service\Gateway')
                 ->disableOriginalConstructor()
                 ->getMock()
        );
    }

    public function testCreateService()
    {
        $this->assertInstanceOf(
            "Techtree\Service\PersonellService",
            $this->sm->get('Techtree\Service\PersonellService')
        );
    }
}