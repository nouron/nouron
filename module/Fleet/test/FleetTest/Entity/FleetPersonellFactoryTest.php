<?php
namespace FleetTest\Entity;

use PHPUnit\Framework\TestCase;
use TechtreeTest\Bootstrap;
use Fleet\Entity\FleetPersonellFactory;

class FleetPersonellFactoryTest extends TestCase
{
    public function setUp(): void
    {
        $this->sm = Bootstrap::getServiceManager();
        #$this->sm->setAllowOverride(true);
    }

    public function testCreateService()
    {
        $factory = new FleetPersonellFactory();
        $this->assertInstanceOf(
            "Fleet\Entity\FleetPersonell",
            $factory($this->sm, '', [])
        );
    }

}