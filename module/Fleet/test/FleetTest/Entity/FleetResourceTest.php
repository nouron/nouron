<?php
namespace FleetTest\Entity;

use Fleet\Entity\FleetResource;
use PHPUnit_Framework_TestCase;

class FleetResourceTest extends PHPUnit_Framework_TestCase
{
    public function testFleetInitialState()
    {
        $fleetResource = new FleetResource();
        $this->assertNull($fleetResource->getFleetId());
        $this->assertNull($fleetResource->getResourceId());
        $this->assertNull($fleetResource->getAmount());
    }

    public function testSetFleetId()
    {
        $fleetResource = new FleetResource();
        $fleetResource->setFleetId(1);
        $this->assertEquals(1, $fleetResource->getFleetId());
        $fleetResource->setFleetId(99);
        $this->assertEquals(99, $fleetResource->getFleetId());

        $this->setExpectedException('Core\Entity\Exception');
        $fleetResource->setFleetId('a');
    }

    public function testSetResourceId()
    {
        $fleetResource = new FleetResource();
        $fleetResource->setResourceId(1);
        $this->assertEquals(1, $fleetResource->getResourceId());
        $fleetResource->setResourceId(99);
        $this->assertEquals(99, $fleetResource->getResourceId());

        $this->setExpectedException('Core\Entity\Exception');
        $fleetResource->setResourceId('a');
    }

    public function testSetAmount()
    {
        $fleetResource = new FleetResource();
        $fleetResource->setAmount(1);
        $this->assertEquals(1, $fleetResource->getAmount());
        $fleetResource->setAmount(99);
        $this->assertEquals(99, $fleetResource->getAmount());

        $this->setExpectedException('Core\Entity\Exception');
        $fleetResource->setAmount('a');
    }

}
