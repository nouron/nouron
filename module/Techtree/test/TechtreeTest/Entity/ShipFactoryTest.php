<?php
namespace TechtreeTest\Entity;

use PHPUnit_Framework_TestCase;
use TechtreeTest\Bootstrap;
use Techtree\Entity\ShipFactory;

class ShipFactoryTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->sm = Bootstrap::getServiceManager();
        #$this->sm->setAllowOverride(true);
    }

    public function testCreateService()
    {
        $factory = new ShipFactory();
        $this->assertInstanceOf(
            "Techtree\Entity\Ship",
            $factory->createService($this->sm)
        );
    }

}