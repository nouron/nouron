<?php
namespace TechtreeTest\Entity;

use PHPUnit\Framework\TestCase;
use TechtreeTest\Bootstrap;
use Techtree\Entity\ColonyPersonellFactory;

class ColonyPersonellFactoryTest extends TestCase
{
    public function setUp(): void
    {
        $this->sm = Bootstrap::getServiceManager();
        #$this->sm->setAllowOverride(true);
    }

    public function testCreateService()
    {
        $factory = new ColonyPersonellFactory();
        $this->assertInstanceOf(
            "Techtree\Entity\ColonyPersonell",
            $factory($this->sm, '', [])
        );
    }

}