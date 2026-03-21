<?php
namespace GalaxyTest\Entity;

use PHPUnit\Framework\TestCase;
use TechtreeTest\Bootstrap;
use Galaxy\Entity\SystemFactory;

class SystemFactoryTest extends TestCase
{
    public function setUp(): void
    {
        $this->sm = Bootstrap::getServiceManager();
        #$this->sm->setAllowOverride(true);
    }

    public function testCreateService()
    {
        $factory = new SystemFactory();
        $this->assertInstanceOf(
            "Galaxy\Entity\System",
            $factory($this->sm, '', [])
        );
    }

}