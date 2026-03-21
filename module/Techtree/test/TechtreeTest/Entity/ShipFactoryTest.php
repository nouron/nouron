<?php
namespace TechtreeTest\Entity;

use PHPUnit\Framework\TestCase;
use TechtreeTest\Bootstrap;
use Techtree\Entity\ShipFactory;

class ShipFactoryTest extends TestCase
{
    public function setUp(): void
    {
        $this->sm = Bootstrap::getServiceManager();
        #$this->sm->setAllowOverride(true);
    }

    public function testCreateService()
    {
        $factory = new ShipFactory();
        $this->assertInstanceOf(
            "Techtree\Entity\Ship",
            $factory($this->sm, '', [])
        );
    }

}