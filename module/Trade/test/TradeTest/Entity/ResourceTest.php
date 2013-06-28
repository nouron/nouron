<?php
namespace TradeTest\Entity;

use Trade\Entity\Resource;
use PHPUnit_Framework_TestCase;

class ResourceTest extends PHPUnit_Framework_TestCase
{
    public function testResourceInitialState()
    {
        $resource = new Resource();
        $this->assertNull($resource->colony_id, '"id" should initially be null');
        $this->assertNull($resource->direction, '"name" should initially be null');
        $this->assertNull($resource->resource_id, '"resource_id" should initially be null');
        $this->assertNull($resource->amount, '"amount" should initially be null');
        $this->assertNull($resource->price, '"price" should initially be null');
        $this->assertNull($resource->restriction, '"restriction" should initially be null');
    }

    public function testGetArrayCopy()
    {
        $resource = new Resource();
        #$this->assertType('Array', $resource->getArrayCopy(), 'expected array is not an array');
        $this->assertArrayHasKey('colony_id', $resource->getArrayCopy());
    }
}