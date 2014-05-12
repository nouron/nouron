<?php
namespace GalaxyTest\Entity;

use PHPUnit_Framework_TestCase;
use TechtreeTest\Bootstrap;
use Galaxy\Entity\SystemObjectFactory;

class SystemObjectFactoryTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->sm = Bootstrap::getServiceManager();
        #$this->sm->setAllowOverride(true);
    }

    public function testCreateService()
    {
        $factory = new SystemObjectFactory();
        $this->assertInstanceOf(
            "Galaxy\Entity\SystemObject",
            $factory->createService($this->sm)
        );
    }

}