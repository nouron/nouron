<?php
namespace GalaxyTest\Entity;

use Galaxy\Entity\SystemObject;
use PHPUnit_Framework_TestCase;

class SystemObjectTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->entity = new SystemObject();
    }

    public function testFleetInitialState()
    {
        $entity = new SystemObject();
        $this->assertNull($entity->getId());
        $this->assertNull($entity->getName());
        $this->assertNull($entity->getX());
        $this->assertNull($entity->getY());
        $this->assertNull($entity->getTypeId());
        $this->assertNull($entity->getSight());
        $this->assertNull($entity->getDensity());
        $this->assertNull($entity->getRadiation());
        $this->assertNull($entity->getImageUrl());
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

    public function testSetX()
    {
        $this->entity->setX(1);
        $this->assertEquals(1, $this->entity->getX());
    }

    public function testSetY()
    {
        $this->entity->setY(1);
        $this->assertEquals(1, $this->entity->getY());
    }

    public function testSetTypeId()
    {
        $this->entity->setTypeId(1);
        $this->assertEquals(1, $this->entity->getTypeId());
    }

    public function testSetSight()
    {
        $this->entity->setSight(1);
        $this->assertEquals(1, $this->entity->getSight());
    }

    public function testSetDensity()
    {
        $this->entity->setDensity(1);
        $this->assertEquals(1, $this->entity->getDensity());
    }

    public function testSetRadiation()
    {
        $this->entity->setRadiation(1);
        $this->assertEquals(1, $this->entity->getRadiation());
    }

    public function testSetImageUrl()
    {
        $this->entity->setImageUrl(1);
        $this->assertEquals(1, $this->entity->getImageUrl());
    }

}