<?php
namespace INNNTest\Entity;

use PHPUnit\Framework\TestCase;
use INNNTest\Bootstrap;
use INNN\Entity\MessageFactory;

class MassageFactoryTest extends TestCase
{
    public function setUp(): void
    {
        $this->sm = Bootstrap::getServiceManager();
        #$this->sm->setAllowOverride(true);
    }

    public function testCreateService()
    {
        $factory = new MessageFactory();
        $this->assertInstanceOf(
            "INNN\Entity\Message",
            $factory($this->sm, '', [])
        );
    }

}