<?php
namespace TechtreeTest\Entity;

use PHPUnit_Framework_TestCase;
use TechtreeTest\Bootstrap;
use Techtree\Entity\ActionPointFactory;

class ActionPointFactoryTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->sm = Bootstrap::getServiceManager();
        #$this->sm->setAllowOverride(true);
    }

    public function testCreateService()
    {
        $factory = new ActionPointFactory();
        $this->assertInstanceOf(
            "Techtree\Entity\ActionPoint",
            $factory->createService($this->sm)
        );
    }

}