<?php

namespace TradeTest\Controller;

use Zend\Test\PHPUnit\Controller\AbstractHttpControllerTestCase;

class IndexControllerTest extends AbstractHttpControllerTestCase
{
    protected $traceError = true;

    public function setUp()
    {
        $this->setApplicationConfig(
            #include '../../../../../config/application.config.php'
            include 'd:/htdocs/nouron/nouron/config/application.config.php'
        );
        parent::setUp();
    }

    public function testTechnologiesActionCanBeAccessed()
    {
        $this->dispatch('/trade/technologies');
        $this->assertResponseStatusCode(200);

        $this->assertModuleName('Trade');
        $this->assertControllerName('Trade\Controller\Index');
        $this->assertControllerClass('IndexController');
        $this->assertMatchedRouteName('trade');
    }
}