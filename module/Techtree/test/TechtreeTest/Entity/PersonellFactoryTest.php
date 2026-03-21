<?php
namespace TechtreeTest\Entity;

use PHPUnit\Framework\TestCase;
use TechtreeTest\Bootstrap;
use Techtree\Entity\PersonellFactory;

class PersonellFactoryTest extends TestCase
{
    public function setUp(): void
    {
        $this->sm = Bootstrap::getServiceManager();
        #$this->sm->setAllowOverride(true);
    }

    public function testCreateService()
    {
        $factory = new PersonellFactory();
        $this->assertInstanceOf(
            "Techtree\Entity\Personell",
            $factory($this->sm, '', [])
        );
    }

}