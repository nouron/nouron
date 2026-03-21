<?php
namespace TechtreeTest\Entity;

use Techtree\Entity\Building;
use PHPUnit\Framework\TestCase;

class BuildingTest extends TestCase
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
    }

    public function testSetMaxLevel()
    {
        $entity = new Building();
        $this->assertNull($entity->getMaxLevel());
        $entity->setMaxLevel(50);
        $this->assertEquals(50, $entity->getMaxLevel());
        $entity->setMaxLevel(0);
        $this->assertEquals(0, $entity->getMaxLevel());
    }

}