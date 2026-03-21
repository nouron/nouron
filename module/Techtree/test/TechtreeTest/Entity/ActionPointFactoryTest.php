<?php
namespace TechtreeTest\Entity;

use PHPUnit\Framework\TestCase;
use TechtreeTest\Bootstrap;
use Techtree\Entity\ActionPointFactory;

class ActionPointFactoryTest extends TestCase
{
    public function setUp(): void
    {
        $this->sm = Bootstrap::getServiceManager();
        #$this->sm->setAllowOverride(true);
    }

    public function testCreateService()
    {
        $factory = new ActionPointFactory();
        $this->assertInstanceOf(
            "Techtree\Entity\ActionPoint",
            $factory($this->sm, '', [])
        );
    }

}