<?php
namespace TechtreeTest\Service;

use PHPUnit_Framework_TestCase;
use TechtreeTest\Bootstrap;

class ShipServiceFactoryTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
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
            $this->getMockBuilder('Zend\Log\Logger')
                  ->disableOriginalConstructor()
                  ->getMock()
        );

        $this->sm->setService(
            'Techtree\Table\ShipTable',
            $this->getMockBuilder('Techtree\Table\ShipTable')
                 ->disableOriginalConstructor()
                 ->getMock()
        );

        $this->sm->setService(
            'Techtree\Table\ShipCostTable',
            $this->getMockBuilder('Techtree\Table\ShipCostTable')
                 ->disableOriginalConstructor()
                 ->getMock()
        );

        $this->sm->setService(
            'Techtree\Table\ColonyShipTable',
            $this->getMockBuilder('Techtree\Table\ColonyShipTable')
                 ->disableOriginalConstructor()
                 ->getMock()
        );

        $this->sm->setService(
            'Galaxy\Table\ColonyTable',
            $this->getMockBuilder('Galaxy\Table\ColonyTable')
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
            "Techtree\Service\ShipService",
            $this->sm->get('Techtree\Service\ShipService')
        );
    }
}