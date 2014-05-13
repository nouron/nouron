<?php
namespace FleetTest\Entity;

use PHPUnit_Framework_TestCase;
use TechtreeTest\Bootstrap;
use Fleet\Entity\FleetPersonellFactory;

class FleetPersonellFactoryTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->sm = Bootstrap::getServiceManager();
        #$this->sm->setAllowOverride(true);
    }

    public function testCreateService()
    {
        $factory = new FleetPersonellFactory();
        $this->assertInstanceOf(
            "Fleet\Entity\FleetPersonell",
            $factory->createService($this->sm)
        );
    }

}