<?php
namespace TechtreeTest\Entity;

use PHPUnit_Framework_TestCase;
use TechtreeTest\Bootstrap;
use Techtree\Entity\ShipCostFactory;

class ShipCostFactoryTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->sm = Bootstrap::getServiceManager();
        #$this->sm->setAllowOverride(true);
    }

    public function testCreateService()
    {
        $factory = new ShipCostFactory();
        $this->assertInstanceOf(
            "Techtree\Entity\ShipCost",
            $factory->createService($this->sm)
        );
    }

}