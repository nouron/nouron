<?php
namespace INNNTest\Entity;

use PHPUnit_Framework_TestCase;
use INNNTest\Bootstrap;
use INNN\Entity\MessageViewFactory;

class MassageViewFactoryTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->sm = Bootstrap::getServiceManager();
        #$this->sm->setAllowOverride(true);
    }

    public function testCreateService()
    {
        $factory = new MessageViewFactory();
        $this->assertInstanceOf(
            "INNN\Entity\Message",
            $factory->createService($this->sm)
        );
    }

}