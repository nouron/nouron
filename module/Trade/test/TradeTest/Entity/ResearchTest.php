<?php
namespace TradeTest\Entity;

use Trade\Entity\Research;
use PHPUnit_Framework_TestCase;

class ResearchTest extends PHPUnit_Framework_TestCase
{
    public function testResourceInitialState()
    {
        $tech = new Research();
        $this->assertNull($tech->colony_id, '"id" should initially be null');
        $this->assertNull($tech->direction, '"name" should initially be null');
        $this->assertNull($tech->research_id, '"research_id" should initially be null');
        $this->assertNull($tech->amount, '"amount" should initially be null');
        $this->assertNull($tech->price, '"price" should initially be null');
        $this->assertNull($tech->restriction, '"restriction" should initially be null');
    }

}