<?php
namespace TradeTest\Entity;

use PHPUnit\Framework\TestCase;
use TradeTest\Bootstrap;
use Trade\Entity\ResearchFactory;

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
            "Trade\Entity\Research",
            $factory($this->sm, '', [])
        );
    }

}