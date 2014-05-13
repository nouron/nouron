<?php
namespace FleetTest\Entity;

use PHPUnit_Framework_TestCase;
use TechtreeTest\Bootstrap;
use Fleet\Entity\FleetOrderFactory;

class FleetOrderFactoryTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->sm = Bootstrap::getServiceManager();
        #$this->sm->setAllowOverride(true);
    }

    public function testCreateService()
    {
        $factory = new FleetOrderFactory();
        $this->assertInstanceOf(
            "Fleet\Entity\FleetOrder",
            $factory->createService($this->sm)
        );
    }

}