<?php
namespace TechtreeTest\Entity;

use Techtree\Entity\Building;
use PHPUnit_Framework_TestCase;

class BuildingTest extends PHPUnit_Framework_TestCase
{
    public function testFleetInitialState()
    {
        $entity = new Building();
        $this->assertNull($entity->getId());
        $this->assertNull($entity->getPrimeColonyOnly());
        $this->assertNull($entity->getMaxLevel());
    }

    public function testSetPrimeColonyOnly()
    {
        $entity = new Building();
        $this->assertNull($entity->getPrimeColonyOnly());
        $entity->setPrimeColonyOnly(true);
        $this->assertTrue($entity->getPrimeColonyOnly());
        $entity->setPrimeColonyOnly(false);
        $this->assertFalse($entity->getPrimeColonyOnly());
        #$entity->setPrimeColonyOnly(true);
        #$this->assertTrue($entity->getPrimeColonyOnly());

        $this->markTestIncomplete();
    }

    public function testSetMaxLevel()
    {
        $entity = new Building();
        $this->assertNull($entity->getMaxLevel());
        $entity->setMaxLevel(50);
        $this->assertEquals(50, $entity->getMaxLevel());

        $this->markTestIncomplete();
    }

}