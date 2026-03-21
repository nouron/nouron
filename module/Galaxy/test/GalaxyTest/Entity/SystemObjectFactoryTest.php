<?php
namespace GalaxyTest\Entity;

use PHPUnit\Framework\TestCase;
use TechtreeTest\Bootstrap;
use Galaxy\Entity\SystemObjectFactory;

class SystemObjectFactoryTest extends TestCase
{
    public function setUp(): void
    {
        $this->sm = Bootstrap::getServiceManager();
        #$this->sm->setAllowOverride(true);
    }

    public function testCreateService()
    {
        $factory = new SystemObjectFactory();
        $this->assertInstanceOf(
            "Galaxy\Entity\SystemObject",
            $factory($this->sm, '', [])
        );
    }

}