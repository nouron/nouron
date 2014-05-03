<?php
namespace TechtreeTest\Entity;

use PHPUnit_Framework_TestCase;
use TechtreeTest\Bootstrap;
use Techtree\Entity\PersonellCostFactory;

class PersonellCostFactoryTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->sm = Bootstrap::getServiceManager();
        #$this->sm->setAllowOverride(true);
    }

    public function testCreateService()
    {
        $factory = new PersonellCostFactory();
        $this->assertInstanceOf(
            "Techtree\Entity\PersonellCost",
            $factory->createService($this->sm)
        );
    }

}