<?php
namespace INNNTest\Entity;

use PHPUnit\Framework\TestCase;
use INNNTest\Bootstrap;
use INNN\Entity\EventFactory;

class EventFactoryTest extends TestCase
{
    public function setUp(): void
    {
        $this->sm = Bootstrap::getServiceManager();
        #$this->sm->setAllowOverride(true);
    }

    public function testCreateService()
    {
        $factory = new EventFactory();
        $this->assertInstanceOf(
            "INNN\Entity\Event",
            $factory($this->sm, '', [])
        );
    }

}