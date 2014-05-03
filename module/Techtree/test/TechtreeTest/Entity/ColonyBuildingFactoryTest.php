<?php
namespace TechtreeTest\Entity;

use PHPUnit_Framework_TestCase;
use TechtreeTest\Bootstrap;
use Techtree\Entity\ColonyBuildingFactory;

class ColonyBuildingFactoryTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->sm = Bootstrap::getServiceManager();
        #$this->sm->setAllowOverride(true);
    }

    public function testCreateService()
    {
        $factory = new ColonyBuildingFactory();
        $this->assertInstanceOf(
            "Techtree\Entity\ColonyBuilding",
            $factory->createService($this->sm)
        );
    }

}