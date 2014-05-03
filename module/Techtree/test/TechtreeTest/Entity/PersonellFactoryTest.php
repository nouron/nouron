<?php
namespace TechtreeTest\Entity;

use PHPUnit_Framework_TestCase;
use TechtreeTest\Bootstrap;
use Techtree\Entity\PersonellFactory;

class PersonellFactoryTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->sm = Bootstrap::getServiceManager();
        #$this->sm->setAllowOverride(true);
    }

    public function testCreateService()
    {
        $factory = new PersonellFactory();
        $this->assertInstanceOf(
            "Techtree\Entity\Personell",
            $factory->createService($this->sm)
        );
    }

}