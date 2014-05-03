<?php
namespace TechtreeTest\Entity;

use PHPUnit_Framework_TestCase;
use TechtreeTest\Bootstrap;
use Techtree\Entity\BuildingCostFactory;

class BuildingCostFactoryTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->sm = Bootstrap::getServiceManager();
        #$this->sm->setAllowOverride(true);
    }

    public function testCreateService()
    {
        $factory = new BuildingCostFactory();
        $this->assertInstanceOf(
            "Techtree\Entity\BuildingCost",
            $factory->createService($this->sm)
        );
    }

}