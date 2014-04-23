<?php
namespace GalaxyTest\Service;

use PHPUnit_Framework_TestCase;
use NouronTest\Service\AbstractServiceTest;

class GatewayTest extends AbstractServiceTest
{
    public function setUp()
    {
        $this->initDatabaseAdapter();
    }

    public function testGatewayInitialState()
    {
        $this->markTestSkipped();
    }
}