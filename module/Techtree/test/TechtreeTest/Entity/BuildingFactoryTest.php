<?php
namespace TechtreeTest\Entity;

use PHPUnit_Framework_TestCase;
use TechtreeTest\Bootstrap;
use Techtree\Entity\BuildingFactory;

class BuildingFactoryTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->sm = Bootstrap::getServiceManager();
        #$this->sm->setAllowOverride(true);
    }

    public function testCreateService()
    {
        $factory = new BuildingFactory();
        $this->assertInstanceOf(
            "Techtree\Entity\Building",
            $factory->createService($this->sm)
        );
    }

}