<?php
namespace FleetTest\Entity;

use PHPUnit\Framework\TestCase;
use TechtreeTest\Bootstrap;
use Fleet\Entity\FleetOrderFactory;

class FleetOrderFactoryTest extends TestCase
{
    public function setUp(): void
    {
        $this->sm = Bootstrap::getServiceManager();
        #$this->sm->setAllowOverride(true);
    }

    public function testCreateService()
    {
        $factory = new FleetOrderFactory();
        $this->assertInstanceOf(
            "Fleet\Entity\FleetOrder",
            $factory($this->sm, '', [])
        );
    }

}