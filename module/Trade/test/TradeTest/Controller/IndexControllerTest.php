<?php

namespace TradeTest\Controller;

use Zend\Test\PHPUnit\Controller\AbstractHttpControllerTestCase;

class IndexControllerTest extends AbstractHttpControllerTestCase
{
    protected $traceError = true;

    public function setUp()
    {
        $basePath = __DIR__ . '/../../../../../';
        $this->setApplicationConfig(
            include $basePath . 'config/application.config.php'
        );
        parent::setUp();
    }

    public function testResearchesActionCanBeAccessed()
    {
        $this->dispatch('/trade/researches');

        $this->markTestSkipped();
//         $this->assertResponseStatusCode(200);

//         $this->assertModuleName('Trade');
//         $this->assertControllerName('Trade\Controller\Index');
//         $this->assertControllerClass('IndexController');
//         $this->assertMatchedRouteName('trade');
    }

    public function testResourcesActionCanBeAccessed()
    {
        $this->dispatch('/trade/resources');

        $this->markTestSkipped();
//         $this->assertResponseStatusCode(200);

//         $this->assertModuleName('Trade');
//         $this->assertControllerName('Trade\Controller\Index');
//         $this->assertControllerClass('IndexController');
//         $this->assertMatchedRouteName('trade');
    }
}