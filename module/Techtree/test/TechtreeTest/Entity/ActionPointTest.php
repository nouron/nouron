<?php
namespace TechtreeTest\Entity;

use Techtree\Entity\ActionPoint;
use PHPUnit_Framework_TestCase;

class ActionPointTest extends PHPUnit_Framework_TestCase
{
    public function testFleetInitialState()
    {
        $entity = new ActionPoint();
        $this->assertNull($entity->getTick());
        $this->assertNull($entity->getColonyId());
        $this->assertNull($entity->getPersonellId());
        $this->assertNull($entity->getSpendAp());
    }

    public function testSetTick()
    {
        $entity = new ActionPoint();
        $this->assertNull($entity->getTick());
        $entity->setTick(12345);
        $this->assertEquals(12345, $entity->getTick());

        $this->setExpectedException('Nouron\Entity\Exception');
        $entity->setTick(-12345);
    }

    public function testSetColonyId()
    {
        $entity = new ActionPoint();
        $entity->setColonyId(1);
        $this->assertEquals(1, $entity->getColonyId());
        $entity->setColonyId(99);
        $this->assertEquals(99, $entity->getColonyId());

        $this->setExpectedException('Nouron\Entity\Exception');
        $entity->setColonyId('a');
    }

    public function testSetPersonellId()
    {
        $entity = new ActionPoint();
        $entity->setPersonellId(1);
        $this->assertEquals(1, $entity->getPersonellId());
        $entity->setPersonellId(99);
        $this->assertEquals(99, $entity->getPersonellId());

        $this->setExpectedException('Nouron\Entity\Exception');
        $entity->setPersonellId('a');
    }

    public function testSetSpendAp()
    {
        $entity = new ActionPoint();
        $this->assertNull($entity->getSpendAp());
        $entity->setSpendAp(12345);
        $this->assertEquals(12345, $entity->getSpendAp());

        $this->setExpectedException('Nouron\Entity\Exception');
        $entity->setSpendAp(-12345);
    }

}