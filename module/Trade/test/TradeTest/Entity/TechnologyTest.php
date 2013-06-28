<?php
namespace TradeTest\Entity;

use Trade\Entity\Technology;
use PHPUnit_Framework_TestCase;

class TechnologyTest extends PHPUnit_Framework_TestCase
{
    public function testResourceInitialState()
    {
        $tech = new Technology();
        $this->assertNull($tech->colony_id, '"id" should initially be null');
        $this->assertNull($tech->direction, '"name" should initially be null');
        $this->assertNull($tech->tech_id, '"resource_id" should initially be null');
        $this->assertNull($tech->amount, '"amount" should initially be null');
        $this->assertNull($tech->price, '"price" should initially be null');
        $this->assertNull($tech->restriction, '"restriction" should initially be null');
    }

    public function testGetArrayCopy()
    {
        $tech = new Technology();
        #$this->assertType('Array', $resource->getArrayCopy(), 'expected array is not an array');
        $this->assertArrayHasKey('colony_id', $tech->getArrayCopy());
    }
}