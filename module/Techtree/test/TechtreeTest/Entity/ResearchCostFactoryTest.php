<?php
namespace TechtreeTest\Entity;

use PHPUnit\Framework\TestCase;
use TechtreeTest\Bootstrap;
use Techtree\Entity\ResearchCostFactory;

class ResearchCostFactoryTest extends TestCase
{
    public function setUp(): void
    {
        $this->sm = Bootstrap::getServiceManager();
        #$this->sm->setAllowOverride(true);
    }

    public function testCreateService()
    {
        $factory = new ResearchCostFactory();
        $this->assertInstanceOf(
            "Techtree\Entity\ResearchCost",
            $factory($this->sm, '', [])
        );
    }

}