<?php
namespace FleetTest\Entity;

use PHPUnit\Framework\TestCase;
use TechtreeTest\Bootstrap;
use Fleet\Entity\FleetResearchFactory;

class FleetResearchFactoryTest extends TestCase
{
    public function setUp(): void
    {
        $this->sm = Bootstrap::getServiceManager();
        #$this->sm->setAllowOverride(true);
    }

    public function testCreateService()
    {
        $factory = new FleetResearchFactory();
        $this->assertInstanceOf(
            "Fleet\Entity\FleetResearch",
            $factory($this->sm, '', [])
        );
    }

}