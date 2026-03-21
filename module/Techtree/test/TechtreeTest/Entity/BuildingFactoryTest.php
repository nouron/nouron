<?php
namespace TechtreeTest\Entity;

use PHPUnit\Framework\TestCase;
use TechtreeTest\Bootstrap;
use Techtree\Entity\BuildingFactory;

class BuildingFactoryTest extends TestCase
{
    public function setUp(): void
    {
        $this->sm = Bootstrap::getServiceManager();
        #$this->sm->setAllowOverride(true);
    }

    public function testCreateService()
    {
        $factory = new BuildingFactory();
        $this->assertInstanceOf(
            "Techtree\Entity\Building",
            $factory($this->sm, '', [])
        );
    }

}