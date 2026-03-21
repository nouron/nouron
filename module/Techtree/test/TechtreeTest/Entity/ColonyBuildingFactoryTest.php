<?php
namespace TechtreeTest\Entity;

use PHPUnit\Framework\TestCase;
use TechtreeTest\Bootstrap;
use Techtree\Entity\ColonyBuildingFactory;

class ColonyBuildingFactoryTest extends TestCase
{
    public function setUp(): void
    {
        $this->sm = Bootstrap::getServiceManager();
        #$this->sm->setAllowOverride(true);
    }

    public function testCreateService()
    {
        $factory = new ColonyBuildingFactory();
        $this->assertInstanceOf(
            "Techtree\Entity\ColonyBuilding",
            $factory($this->sm, '', [])
        );
    }

}