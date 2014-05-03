<?php
namespace TechtreeTest\Entity;

use PHPUnit_Framework_TestCase;
use TechtreeTest\Bootstrap;
use Techtree\Entity\ColonyResearchFactory;

class ColonyResearchFactoryTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->sm = Bootstrap::getServiceManager();
        #$this->sm->setAllowOverride(true);
    }

    public function testCreateService()
    {
        $factory = new ColonyResearchFactory();
        $this->assertInstanceOf(
            "Techtree\Entity\ColonyResearch",
            $factory->createService($this->sm)
        );
    }

}