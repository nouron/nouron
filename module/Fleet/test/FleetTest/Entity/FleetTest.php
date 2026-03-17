<?php
namespace FleetTest\Entity;

use Fleet\Entity\Fleet;
use PHPUnit\Framework\TestCase;

class FleetTest extends TestCase
{
    public function testFleetInitialState()
    {
        $fleet = new Fleet();
        $this->assertNull($fleet->getId());
        $this->assertNull($fleet->getFleet());
        $this->assertNull($fleet->getUserId());
        $this->assertNull($fleet->getX());
        $this->assertNull($fleet->getY());
        $this->assertNull($fleet->getSpot());
    }


    public function testSetId()
    {
        $fleet = new Fleet();
        $fleet->setId(1);
        $this->assertEquals(1, $fleet->getId());
        $fleet->setId(99);
        $this->assertEquals(99, $fleet->getId());

        $this->expectException('Core\Entity\Exception');
        $fleet->setId('a');
    }

    public function testSetFleet()
    {
        $fleet = new Fleet();
        $fleet->setFleet('Testfleet');
        $this->assertEquals('Testfleet', $fleet->getFleet());
        $fleet->setFleet('');
        $this->assertEquals('', $fleet->getFleet());
    }

    public function testSetUserId()
    {
        $fleet = new Fleet();
        $fleet->setUserId(1);
        $this->assertEquals(1, $fleet->getUserId());
        $fleet->setUserId(99);
        $this->assertEquals(99, $fleet->getUserId());

        $this->expectException('Core\Entity\Exception');
        $fleet->setUserId('a');
    }

    public function testSetX()
    {
        $fleet = new Fleet();
        $fleet->setX(1);
        $this->assertEquals(1, $fleet->getX());
        $fleet->setX(99);
        $this->assertEquals(99, $fleet->getX());
        $fleet->setX(-99);
        $this->assertEquals(-99, $fleet->getX());

        $this->expectException('Core\Entity\Exception');
        $fleet->setX('a');
    }

    public function testSetY()
    {
        $fleet = new Fleet();
        $fleet->setY(1);
        $this->assertEquals(1, $fleet->getY());
        $fleet->setY(99);
        $this->assertEquals(99, $fleet->getY());
        $fleet->setY(-99);
        $this->assertEquals(-99, $fleet->getY());

        $this->expectException('Core\Entity\Exception');
        $fleet->setY('a');
    }

    public function testSetSpot()
    {
        $fleet = new Fleet();
        $fleet->setSpot(9);
        $this->assertEquals(9, $fleet->getSpot());
        $fleet->setSpot(0);
        $this->assertEquals(0, $fleet->getSpot());

        $this->expectException('Core\Entity\Exception');
        $fleet->setSpot('a');
    }

    public function testGetCoords()
    {
        $fleet = new Fleet();
        $fleet->setX(12345);
        $fleet->setY(-12345);
        $fleet->setSpot(9);
        $coords = $fleet->getCoords();
        $this->assertTrue(is_array($coords));
        $this->assertEquals(serialize(array(12345,-12345,9)), serialize($coords));
    }
}