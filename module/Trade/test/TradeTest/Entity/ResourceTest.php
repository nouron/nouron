<?php
namespace TradeTest\Entity;

use Trade\Entity\Resource;
use PHPUnit_Framework_TestCase;

class ResourceTest extends PHPUnit_Framework_TestCase
{
    public function testResourceInitialState()
    {
        $resource = new Resource();
        $this->assertNull($resource->getColonyId());
        $this->assertNull($resource->getDirection());
        $this->assertNull($resource->getResourceId());
        $this->assertNull($resource->getAmount());
        $this->assertNull($resource->getPrice());
        $this->assertNull($resource->getRestriction());
        $this->assertNull($resource->getColony());
        $this->assertNull($resource->getUsername());
        $this->assertNull($resource->getUserId());
        $this->assertNull($resource->getRaceId());
        $this->assertNull($resource->getFactionId());
    }

}