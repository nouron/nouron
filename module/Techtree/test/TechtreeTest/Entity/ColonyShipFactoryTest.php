<?php
namespace TechtreeTest\Entity;

use PHPUnit_Framework_TestCase;
use TechtreeTest\Bootstrap;
use Techtree\Entity\ColonyShipFactory;

class ColonyShipFactoryTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->sm = Bootstrap::getServiceManager();
        #$this->sm->setAllowOverride(true);
    }

    public function testCreateService()
    {
        $factory = new ColonyShipFactory();
        $this->assertInstanceOf(
            "Techtree\Entity\ColonyShip",
            $factory->createService($this->sm)
        );
    }

}