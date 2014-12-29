<?php
namespace FleetTest\Entity;

use Fleet\Entity\Fleet;
use PHPUnit_Framework_TestCase;

class FleetTest extends PHPUnit_Framework_TestCase
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

        $this->setExpectedException('Core\Entity\Exception');
        $fleet->setId('a');

        $this->markTestIncomplete();

    }

    public function testSetFleet()
    {
        $fleet = new Fleet();
        $fleet->setFleet('Testfleet');
        $this->assertEquals('Testfleet', $fleet->getFleet());

        $this->markTestIncomplete();

    }

    public function testSetUserId()
    {
        $fleet = new Fleet();
        $fleet->setUserId(1);
        $this->assertEquals(1, $fleet->getUserId());
        $fleet->setUserId(99);
        $this->assertEquals(99, $fleet->getUserId());

        $this->setExpectedException('Core\Entity\Exception');
        $fleet->setUserId('a');

        $this->markTestIncomplete();

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

        $this->setExpectedException('Core\Entity\Exception');
        $fleet->setX('a');

        // TODO: test edge cases
        $this->markTestIncomplete();

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

        $this->setExpectedException('Core\Entity\Exception');
        $fleet->setY('a');

        // TODO: test edge cases
        $this->markTestIncomplete();

    }

    public function testSetSpot()
    {
        $fleet = new Fleet();
        $fleet->setSpot(9);
        $this->assertEquals(9, $fleet->getSpot());

        $this->setExpectedException('Core\Entity\Exception');
        $fleet->setSpot('a');

        // TODO: test edge cases
        $this->markTestIncomplete();

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

        // TODO: test edge cases
        $this->markTestIncomplete();
    }
}