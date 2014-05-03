<?php
namespace TechtreeTest\Entity;

use PHPUnit_Framework_TestCase;
use TechtreeTest\Bootstrap;
use Techtree\Entity\ResearchCostFactory;

class ResearchCostFactoryTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->sm = Bootstrap::getServiceManager();
        #$this->sm->setAllowOverride(true);
    }

    public function testCreateService()
    {
        $factory = new ResearchCostFactory();
        $this->assertInstanceOf(
            "Techtree\Entity\ResearchCost",
            $factory->createService($this->sm)
        );
    }

}