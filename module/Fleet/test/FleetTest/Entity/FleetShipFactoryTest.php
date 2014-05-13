<?php
namespace FleetTest\Entity;

use PHPUnit_Framework_TestCase;
use TechtreeTest\Bootstrap;
use Fleet\Entity\FleetShipFactory;

class FleetShipFactoryTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->sm = Bootstrap::getServiceManager();
        #$this->sm->setAllowOverride(true);
    }

    public function testCreateService()
    {
        $factory = new FleetShipFactory();
        $this->assertInstanceOf(
            "Fleet\Entity\FleetShip",
            $factory->createService($this->sm)
        );
    }

}