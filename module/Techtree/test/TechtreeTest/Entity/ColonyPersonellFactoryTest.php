<?php
namespace TechtreeTest\Entity;

use PHPUnit_Framework_TestCase;
use TechtreeTest\Bootstrap;
use Techtree\Entity\ColonyPersonellFactory;

class ColonyPersonellFactoryTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->sm = Bootstrap::getServiceManager();
        #$this->sm->setAllowOverride(true);
    }

    public function testCreateService()
    {
        $factory = new ColonyPersonellFactory();
        $this->assertInstanceOf(
            "Techtree\Entity\ColonyPersonell",
            $factory->createService($this->sm)
        );
    }

}