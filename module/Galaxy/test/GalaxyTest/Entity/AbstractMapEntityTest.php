<?php
namespace GalaxyTest\Entity;

use GalaxyTest\Entity\DummyMapEntity;
use PHPUnit_Framework_TestCase;

class AbstractMapEntityTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->entity = new DummyMapEntity();
    }

    public function testFleetInitialState()
    {
        $this->assertNull($this->entity->getId());
        $this->assertNull($this->entity->getName());
        $this->assertNull($this->entity->getX());
        $this->assertNull($this->entity->getY());
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
}