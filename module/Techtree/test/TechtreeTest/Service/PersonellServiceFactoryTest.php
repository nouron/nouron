<?php
namespace TechtreeTest\Service;

use PHPUnit_Framework_TestCase;
use TechtreeTest\Bootstrap;

class PersonellServiceFactoryTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->sm = Bootstrap::getServiceManager();
        $this->sm->setAllowOverride(true);

        $this->sm->setService(
            'Nouron\Service\Tick',
            $this->getMockBuilder('Nouron\Service\Tick')
                  ->disableOriginalConstructor()
                  ->getMock()
        );

        $this->sm->setService(
            'logger',
            $this->getMockBuilder('Zend\Log\Logger')
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