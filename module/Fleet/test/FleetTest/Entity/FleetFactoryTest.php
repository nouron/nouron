<?php
namespace FleetTest\Entity;

use PHPUnit\Framework\TestCase;
use FleetTest\Bootstrap;
use Fleet\Entity\FleetFactory;

class FleetFactoryTest extends TestCase
{
    public function setUp(): void
    {
        $this->sm = Bootstrap::getServiceManager();
        #$this->sm->setAllowOverride(true);
    }

    public function testCreateService()
    {
        $factory = new FleetFactory();
        $this->assertInstanceOf(
            "Fleet\Entity\Fleet",
            $factory($this->sm, '', [])
        );
    }

}