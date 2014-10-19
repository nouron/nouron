<?php
namespace INNNTest\Entity;

use PHPUnit_Framework_TestCase;
use TechtreeTest\Bootstrap;
use INNN\Entity\EventFactory;

class EventFactoryTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->sm = Bootstrap::getServiceManager();
        #$this->sm->setAllowOverride(true);
    }

    public function testCreateService()
    {
        $factory = new EventFactory();
        $this->assertInstanceOf(
            "INNN\Entity\Event",
            $factory->createService($this->sm)
        );
    }

}