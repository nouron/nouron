<?php
namespace TechtreeTest\Entity;

use PHPUnit\Framework\TestCase;
use TechtreeTest\Bootstrap;
use Techtree\Entity\PersonellCostFactory;

class PersonellCostFactoryTest extends TestCase
{
    public function setUp(): void
    {
        $this->sm = Bootstrap::getServiceManager();
        #$this->sm->setAllowOverride(true);
    }

    public function testCreateService()
    {
        $factory = new PersonellCostFactory();
        $this->assertInstanceOf(
            "Techtree\Entity\PersonellCost",
            $factory($this->sm, '', [])
        );
    }

}