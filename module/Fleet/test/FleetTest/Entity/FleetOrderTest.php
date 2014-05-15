<?php
namespace FleetTest\Entity;

use Fleet\Entity\FleetOrder;
use PHPUnit_Framework_TestCase;

class FleetOrderTest extends PHPUnit_Framework_TestCase
{
    public function testFleetInitialState()
    {
        $fleetOrder = new FleetOrder();
        $this->assertNull($fleetOrder->getTick());
        $this->assertNull($fleetOrder->getFleetId());
        $this->assertNull($fleetOrder->getOrder());
        $this->assertNull($fleetOrder->getCoordinates());
        $this->assertNull($fleetOrder->getData());
        $this->assertNull($fleetOrder->getWasProcessed());
        $this->assertNull($fleetOrder->getHasNotified());
    }

    public function testSetTick()
    {
        $fleetOrder = new FleetOrder();
        $fleetOrder->setTick(1);
        $this->assertEquals(1, $fleetOrder->getTick());
        $fleetOrder->setTick(99);
        $this->assertEquals(99, $fleetOrder->getTick());

        $this->setExpectedException('Nouron\Entity\Exception');
        $fleetOrder->setTick('a');
    }

    public function testSetFleetId()
    {
        $fleetOrder = new FleetOrder();
        $fleetOrder->setFleetId(1);
        $this->assertEquals(1, $fleetOrder->getFleetId());
        $fleetOrder->setFleetId(99);
        $this->assertEquals(99, $fleetOrder->getFleetId());

        $this->setExpectedException('Nouron\Entity\Exception');
        $fleetOrder->setFleetId('a');
    }

    public function testSetOrder()
    {
        $fleetOrder = new FleetOrder();
        $fleetOrder->setOrder('move');
        $this->assertEquals('move', $fleetOrder->getOrder());

        $fleetOrder->setOrder('attack');
        $this->assertEquals('attack', $fleetOrder->getOrder());

        $this->setExpectedException('Nouron\Entity\Exception');
        $fleetOrder->setOrder(99);
    }

    public function testSetCoordinates()
    {
        $fleetOrder = new FleetOrder();
        $fleetOrder->setCoordinates(array(1,2,3));
        $this->assertEquals(array(1,2,3), $fleetOrder->getCoordinates());
        $fleetOrder->setCoordinates('[1,2,3]');
        $this->assertEquals(array(1,2,3), $fleetOrder->getCoordinates());

        $this->setExpectedException('Nouron\Entity\Exception');
        $fleetOrder->setCoordinates('abc');
    }

    public function testSetData()
    {
        $fleetOrder = new FleetOrder();
        $fleetOrder->setData(array(1,2,3));
        $this->assertEquals(array(1,2,3), $fleetOrder->getData());
        $fleetOrder->setData('[1,2,3]');
        $this->assertEquals(array(1,2,3), $fleetOrder->getData());

        $this->setExpectedException('Nouron\Entity\Exception');
        $fleetOrder->setData('abc');
    }

    public function testSetWasProcessed()
    {
        $fleetOrder = new FleetOrder();
        $fleetOrder->setWasProcessed(1);
        $this->assertTrue($fleetOrder->getWasProcessed());
        $fleetOrder->setWasProcessed(True);
        $this->assertTrue($fleetOrder->getWasProcessed());
        $fleetOrder->setWasProcessed('aaa');
        $this->assertTrue($fleetOrder->getWasProcessed());
        $fleetOrder->setWasProcessed(0);
        $this->assertFalse($fleetOrder->getWasProcessed());
        $fleetOrder->setWasProcessed(False);
        $this->assertFalse($fleetOrder->getWasProcessed());
        $fleetOrder->setWasProcessed(null);
        $this->assertFalse($fleetOrder->getWasProcessed());
    }

    public function testSetHasNotified()
    {
        $fleetOrder = new FleetOrder();
        $fleetOrder->setHasNotified(1);
        $this->assertTrue($fleetOrder->getHasNotified());
        $fleetOrder->setHasNotified(True);
        $this->assertTrue($fleetOrder->getHasNotified());
        $fleetOrder->setHasNotified('aaa');
        $this->assertTrue($fleetOrder->getHasNotified());
        $fleetOrder->setHasNotified(0);
        $this->assertFalse($fleetOrder->getHasNotified());
        $fleetOrder->setHasNotified(False);
        $this->assertFalse($fleetOrder->getHasNotified());
        $fleetOrder->setHasNotified(null);
        $this->assertFalse($fleetOrder->getHasNotified());
    }
}
