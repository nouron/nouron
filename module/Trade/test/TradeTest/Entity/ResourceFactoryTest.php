<?php
namespace TradeTest\Entity;

use PHPUnit\Framework\TestCase;
use TradeTest\Bootstrap;
use Trade\Entity\ResourceFactory;

class ResourceFactoryTest extends TestCase
{
    public function setUp(): void
    {
        $this->sm = Bootstrap::getServiceManager();
        #$this->sm->setAllowOverride(true);
    }

    public function testCreateService()
    {
        $factory = new ResourceFactory();
        $this->assertInstanceOf(
            "Trade\Entity\Resource",
            $factory($this->sm, '', [])
        );
    }

}