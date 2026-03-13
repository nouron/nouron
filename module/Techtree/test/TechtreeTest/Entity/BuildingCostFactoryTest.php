<?php
namespace TechtreeTest\Entity;

use PHPUnit\Framework\TestCase;
use TechtreeTest\Bootstrap;
use Techtree\Entity\BuildingCostFactory;

class BuildingCostFactoryTest extends TestCase
{
    public function setUp(): void
    {
        $this->sm = Bootstrap::getServiceManager();
        #$this->sm->setAllowOverride(true);
    }

    public function testCreateService()
    {
        $factory = new BuildingCostFactory();
        $this->assertInstanceOf(
            "Techtree\Entity\BuildingCost",
            $factory($this->sm, '', [])
        );
    }

}