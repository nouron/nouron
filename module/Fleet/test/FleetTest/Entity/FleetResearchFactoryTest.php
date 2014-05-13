<?php
namespace FleetTest\Entity;

use PHPUnit_Framework_TestCase;
use TechtreeTest\Bootstrap;
use Fleet\Entity\FleetResearchFactory;

class FleetResearchFactoryTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->sm = Bootstrap::getServiceManager();
        #$this->sm->setAllowOverride(true);
    }

    public function testCreateService()
    {
        $factory = new FleetResearchFactory();
        $this->assertInstanceOf(
            "Fleet\Entity\FleetResearch",
            $factory->createService($this->sm)
        );
    }

}