<?php
namespace GalaxyTest\Entity;

use PHPUnit_Framework_TestCase;
use TechtreeTest\Bootstrap;
use Galaxy\Entity\SystemFactory;

class SystemFactoryTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->sm = Bootstrap::getServiceManager();
        #$this->sm->setAllowOverride(true);
    }

    public function testCreateService()
    {
        $factory = new SystemFactory();
        $this->assertInstanceOf(
            "Galaxy\Entity\System",
            $factory->createService($this->sm)
        );
    }

}