<?php
namespace TradeTest\Entity;

use PHPUnit_Framework_TestCase;
use TradeTest\Bootstrap;
use Trade\Entity\ResourceFactory;

class ResourceFactoryTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->sm = Bootstrap::getServiceManager();
        #$this->sm->setAllowOverride(true);
    }

    public function testCreateService()
    {
        $factory = new ResourceFactory();
        $this->assertInstanceOf(
            "Trade\Entity\Resource",
            $factory->createService($this->sm)
        );
    }

}