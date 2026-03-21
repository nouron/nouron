<?php
namespace FleetTest\Entity;

use PHPUnit\Framework\TestCase;
use TechtreeTest\Bootstrap;
use Fleet\Entity\FleetShipFactory;

class FleetShipFactoryTest extends TestCase
{
    public function setUp(): void
    {
        $this->sm = Bootstrap::getServiceManager();
        #$this->sm->setAllowOverride(true);
    }

    public function testCreateService()
    {
        $factory = new FleetShipFactory();
        $this->assertInstanceOf(
            "Fleet\Entity\FleetShip",
            $factory($this->sm, '', [])
        );
    }

}