<?php

namespace GalaxyTest\Controller;

use Zend\Test\PHPUnit\Controller\AbstractHttpControllerTestCase;

class IndexControllerTest extends AbstractHttpControllerTestCase
{
    protected $traceError = true;

    public function setUp()
    {
        print getcwd();
        exit();

        $this->setApplicationConfig(
            #include '../../../../../config/application.config.php'
            include 'd:/htdocs/nouron/nouron/config/application.config.php'
        );
        parent::setUp();
    }

    public function testIndexActionCanBeAccessed()
    {
        $this->dispatch('/galaxy');
        $this->assertResponseStatusCode(200);

        $this->assertModuleName('Galaxy');
        $this->assertControllerName('Galaxy\Controller\Index');
        $this->assertControllerClass('IndexController');
        $this->assertMatchedRouteName('galaxy');
    }
}