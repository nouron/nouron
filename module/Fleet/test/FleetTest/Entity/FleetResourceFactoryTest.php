<?php
namespace FleetTest\Entity;

use PHPUnit\Framework\TestCase;
use TechtreeTest\Bootstrap;
use Fleet\Entity\FleetResourceFactory;

class FleetResourceFactoryTest extends TestCase
{
    public function setUp(): void
    {
        $this->sm = Bootstrap::getServiceManager();
        #$this->sm->setAllowOverride(true);
    }

    public function testCreateService()
    {
        $factory = new FleetResourceFactory();
        $this->assertInstanceOf(
            "Fleet\Entity\FleetResource",
            $factory($this->sm, '', [])
        );
    }

}