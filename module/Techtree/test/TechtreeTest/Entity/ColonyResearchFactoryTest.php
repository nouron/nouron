<?php
namespace TechtreeTest\Entity;

use PHPUnit\Framework\TestCase;
use TechtreeTest\Bootstrap;
use Techtree\Entity\ColonyResearchFactory;

class ColonyResearchFactoryTest extends TestCase
{
    public function setUp(): void
    {
        $this->sm = Bootstrap::getServiceManager();
        #$this->sm->setAllowOverride(true);
    }

    public function testCreateService()
    {
        $factory = new ColonyResearchFactory();
        $this->assertInstanceOf(
            "Techtree\Entity\ColonyResearch",
            $factory($this->sm, '', [])
        );
    }

}