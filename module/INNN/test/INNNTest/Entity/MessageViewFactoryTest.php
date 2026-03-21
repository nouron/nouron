<?php
namespace INNNTest\Entity;

use PHPUnit\Framework\TestCase;
use INNNTest\Bootstrap;
use INNN\Entity\MessageViewFactory;

class MassageViewFactoryTest extends TestCase
{
    public function setUp(): void
    {
        $this->sm = Bootstrap::getServiceManager();
        #$this->sm->setAllowOverride(true);
    }

    public function testCreateService()
    {
        $factory = new MessageViewFactory();
        $this->assertInstanceOf(
            "INNN\Entity\Message",
            $factory($this->sm, '', [])
        );
    }

}