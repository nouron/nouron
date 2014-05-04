<?php
namespace TradeTest\Entity;

use PHPUnit_Framework_TestCase;
use TechtreeTest\Bootstrap;
use Trade\Entity\ResearchFactory;

class ResearchFactoryTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->sm = Bootstrap::getServiceManager();
        #$this->sm->setAllowOverride(true);
    }

    public function testCreateService()
    {
        $factory = new ResearchFactory();
        $this->assertInstanceOf(
            "Trade\Entity\Research",
            $factory->createService($this->sm)
        );
    }

}