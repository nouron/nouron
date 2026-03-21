<?php
namespace TechtreeTest\Entity;

use Techtree\Entity\ActionPoint;
use PHPUnit\Framework\TestCase;

class ActionPointTest extends TestCase
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

        $this->expectException('Core\Entity\Exception');
        $entity->setTick(-12345);
    }

    public function testSetColonyId()
    {
        $entity = new ActionPoint();
        $entity->setColonyId(1);
        $this->assertEquals(1, $entity->getColonyId());
        $entity->setColonyId(99);
        $this->assertEquals(99, $entity->getColonyId());

        $this->expectException('Core\Entity\Exception');
        $entity->setColonyId('a');
    }

    public function testSetPersonellId()
    {
        $entity = new ActionPoint();
        $entity->setPersonellId(1);
        $this->assertEquals(1, $entity->getPersonellId());
        $entity->setPersonellId(99);
        $this->assertEquals(99, $entity->getPersonellId());

        $this->expectException('Core\Entity\Exception');
        $entity->setPersonellId('a');
    }

    public function testSetSpendAp()
    {
        $entity = new ActionPoint();
        $this->assertNull($entity->getSpendAp());
        $entity->setSpendAp(12345);
        $this->assertEquals(12345, $entity->getSpendAp());

        $this->expectException('Core\Entity\Exception');
        $entity->setSpendAp(-12345);
    }

}