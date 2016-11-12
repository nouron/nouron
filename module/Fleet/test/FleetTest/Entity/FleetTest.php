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
        $this->assertNull($fleet->getName());
        $this->assertNull($fleet->getUserId());
        $this->assertNull($fleet->getX());
        $this->assertNull($fleet->getY());
        $this->assertNull($fleet->getSpot());
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

}