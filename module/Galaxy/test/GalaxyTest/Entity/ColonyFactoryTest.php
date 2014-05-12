<?php
namespace GalaxyTest\Entity;

use PHPUnit_Framework_TestCase;
use TechtreeTest\Bootstrap;
use Galaxy\Entity\ColonyFactory;

class ColonyFactoryTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->sm = Bootstrap::getServiceManager();
        #$this->sm->setAllowOverride(true);
    }

    public function testCreateService()
    {
        $factory = new ColonyFactory();
        $this->assertInstanceOf(
            "Galaxy\Entity\Colony",
            $factory->createService($this->sm)
        );
    }

}