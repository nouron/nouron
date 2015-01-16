<?php
namespace FleetTest\Entity;

use PHPUnit_Framework_TestCase;
use FleetTest\Bootstrap;
use Fleet\Entity\FleetFactory;

class FleetFactoryTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->sm = Bootstrap::getServiceManager();
        #$this->sm->setAllowOverride(true);
    }

    public function testCreateService()
    {
        $factory = new FleetFactory();
        $this->assertInstanceOf(
            "Fleet\Entity\Fleet",
            $factory->createService($this->sm)
        );
    }

}