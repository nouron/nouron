<?php
namespace TechtreeTest\Entity;

use PHPUnit\Framework\TestCase;
use TechtreeTest\Bootstrap;
use Techtree\Entity\ResearchFactory;

class ResearchFactoryTest extends TestCase
{
    public function setUp(): void
    {
        $this->sm = Bootstrap::getServiceManager();
        #$this->sm->setAllowOverride(true);
    }

    public function testCreateService()
    {
        $factory = new ResearchFactory();
        $this->assertInstanceOf(
            "Techtree\Entity\Research",
            $factory($this->sm, '', [])
        );
    }

}