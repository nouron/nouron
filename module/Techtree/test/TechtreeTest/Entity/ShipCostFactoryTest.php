<?php
namespace TechtreeTest\Entity;

use PHPUnit\Framework\TestCase;
use TechtreeTest\Bootstrap;
use Techtree\Entity\ShipCostFactory;

class ShipCostFactoryTest extends TestCase
{
    public function setUp(): void
    {
        $this->sm = Bootstrap::getServiceManager();
        #$this->sm->setAllowOverride(true);
    }

    public function testCreateService()
    {
        $factory = new ShipCostFactory();
        $this->assertInstanceOf(
            "Techtree\Entity\ShipCost",
            $factory($this->sm, '', [])
        );
    }

}