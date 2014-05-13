<?php
namespace FleetTest\Entity;

use PHPUnit_Framework_TestCase;
use TechtreeTest\Bootstrap;
use Fleet\Entity\FleetResourceFactory;

class FleetResourceFactoryTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->sm = Bootstrap::getServiceManager();
        #$this->sm->setAllowOverride(true);
    }

    public function testCreateService()
    {
        $factory = new FleetResourceFactory();
        $this->assertInstanceOf(
            "Fleet\Entity\FleetResource",
            $factory->createService($this->sm)
        );
    }

}