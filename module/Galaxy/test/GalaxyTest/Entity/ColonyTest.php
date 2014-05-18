<?php
namespace GalaxyTest\Entity;

use Galaxy\Entity\Colony;
use PHPUnit_Framework_TestCase;

class ColonyTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->entity = new Colony();
    }

    public function testFleetInitialState()
    {
        $entity = new Colony();
        $this->assertNull($entity->getId());
        $this->assertNull($entity->getName());
        $this->assertNull($entity->getSystemObjectId());
        $this->assertNull($entity->getSpot());
        $this->assertNull($entity->getUserId());
        $this->assertNull($entity->getSinceTick());
        $this->assertNull($entity->getIsPrimary());
        $this->assertNull($entity->getSystemObjectName());
        $this->assertNull($entity->getX());
        $this->assertNull($entity->getY());
        $this->assertNull($entity->getTypeId());
        $this->assertNull($entity->getSight());
        $this->assertNull($entity->getDensity());
        $this->assertNull($entity->getRadiation());
    }

    public function testSetId()
    {
        $this->entity->setId(1);
        $this->assertEquals(1, $this->entity->getId());
    }
    public function testSetName()
    {
        $this->entity->setName(1);
        $this->assertEquals(1, $this->entity->getName());
    }
    public function testSetSystemObjectId()
    {
        $this->entity->setSystemObjectId(1);
        $this->assertEquals(1, $this->entity->getSystemObjectId());
    }
    public function testSetSpot()
    {
        $this->entity->setSpot(1);
        $this->assertEquals(1, $this->entity->getSpot());
    }
    public function testSetUserId()
    {
        $this->entity->setUserId(1);
        $this->assertEquals(1, $this->entity->getUserId());
    }
    public function testSetSinceTick()
    {
        $this->entity->setSinceTick(12345);
        $this->assertEquals(12345, $this->entity->getSinceTick());
    }
    public function testSetIsPrimary()
    {
        $this->entity->setIsPrimary(1);
        $this->assertEquals(1, $this->entity->getIsPrimary());
    }
    public function testSetSystemObjectName()
    {
        $this->entity->setSystemObjectName(1);
        $this->assertEquals(1, $this->entity->getSystemObjectName());
    }
    public function testSetX()
    {
        $this->entity->setX(12345);
        $this->assertEquals(12345, $this->entity->getX());
    }
    public function testSetY()
    {
        $this->entity->setY(12345);
        $this->assertEquals(12345, $this->entity->getY());
    }
    public function testSetTypeId()
    {
        $this->entity->setTypeId(1);
        $this->assertEquals(1, $this->entity->getTypeId());
    }
    public function testSetSight()
    {
        $this->entity->setSight(9);
        $this->assertEquals(9, $this->entity->getSight());
    }
    public function testSetDensity()
    {
        $this->entity->setDensity(9);
        $this->assertEquals(9, $this->entity->getDensity());
    }
    public function testSetRadiation()
    {
        $this->entity->setRadiation(9);
        $this->assertEquals(9, $this->entity->getRadiation());
    }

}