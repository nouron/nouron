<?php
namespace TechtreeTest\Entity;

use PHPUnit\Framework\TestCase;
use TechtreeTest\Bootstrap;
use Techtree\Entity\ColonyShipFactory;

class ColonyShipFactoryTest extends TestCase
{
    public function setUp(): void
    {
        $this->sm = Bootstrap::getServiceManager();
        #$this->sm->setAllowOverride(true);
    }

    public function testCreateService()
    {
        $factory = new ColonyShipFactory();
        $this->assertInstanceOf(
            "Techtree\Entity\ColonyShip",
            $factory($this->sm, '', [])
        );
    }

}