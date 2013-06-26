<?php

namespace GalaxyTest\Controller;

use Zend\Test\PHPUnit\Controller\AbstractHttpControllerTestCase;

class IndexControllerTest extends AbstractHttpControllerTestCase
{
    protected $traceError = true;

    public function setUp()
    {
        $this->setApplicationConfig(
            include '../../../config/application.config.php'
        );
        parent::setUp();
    }

    public function testIndexActionCanBeAccessed()
    {
        // $colonyTableMock = $this->getMockBuilder('Galaxy\Table\Colony')
        //                         ->disableOriginalConstructor()
        //                         ->getMock();

        // $colonyTableMock->expects($this->once())
        //                 ->method('fetchAll')
        //                 ->will($this->returnValue(array()));

        // $serviceManager = $this->getApplicationServiceLocator();
        // $serviceManager->setAllowOverride(true);
        // $serviceManager->setService('Galaxy\Table\Colony', $colonyTableMock);

        $this->dispatch('/galaxy');
        $this->assertResponseStatusCode(200);

        $this->assertModuleName('Galaxy');
        $this->assertControllerName('Galaxy\Controller\Index');
        $this->assertControllerClass('IndexController');
        $this->assertMatchedRouteName('galaxy');
    }
}