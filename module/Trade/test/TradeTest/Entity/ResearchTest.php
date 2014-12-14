<?php
namespace TradeTest\Entity;

use Trade\Entity\Research;
use PHPUnit_Framework_TestCase;

class ResearchTest extends PHPUnit_Framework_TestCase
{
    public function testResourceInitialState()
    {
        $research = new Research();
        $this->assertNull($research->getColonyId());
        $this->assertNull($research->getDirection());
        $this->assertNull($research->getResearchId());
        $this->assertNull($research->getAmount());
        $this->assertNull($research->getPrice());
        $this->assertNull($research->getRestriction());
        $this->assertNull($research->getColony());
        $this->assertNull($research->getUsername());
        $this->assertNull($research->getUserId());
        $this->assertNull($research->getRaceId());
        $this->assertNull($research->getFactionId());
    }

}