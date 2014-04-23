<?php

namespace GalaxyTest\Controller;

use Zend\Test\PHPUnit\Controller\AbstractHttpControllerTestCase;
use GalaxyTest\Bootstrap;
use Zend\Http\Request;
use Zend\Mvc\MvcEvent;
use Zend\Mvc\Router\RouteMatch;
use Zend\Mvc\Router\Http\TreeRouteStack as HttpRouter;

class IndexControllerTest extends AbstractHttpControllerTestCase
{
    protected $traceError = true;

    public function setUp()
    {
        $basePath = __DIR__ . '/../../../../../';
        $this->setApplicationConfig(
            include $basePath . 'config/application.config.php'
        );

        $serviceManager = Bootstrap::getServiceManager();
        $this->controller = new \Galaxy\Controller\IndexController();
        $this->request    = new Request();
        $this->routeMatch = new RouteMatch(array('controller' => 'index'));
        $this->event      = new MvcEvent();
        $config = $serviceManager->get('Config');
        $routerConfig = isset($config['router']) ? $config['router'] : array();

        $router = HttpRouter::factory($routerConfig);
        $this->event->setRouter($router);
        $this->event->setRouteMatch($this->routeMatch);
        $this->controller->setEvent($this->event);
        $this->controller->setServiceLocator($serviceManager);
        $mockAuth = $this->getMock('ZfcUser\Entity\UserInterface');

        $ZfcUserMock = $this->getMock('User\Entity\User');

        $ZfcUserMock->expects($this->any())
                    ->method('getId')
                    ->will($this->returnValue('3'));

        $authMock = $this->getMock('ZfcUser\Controller\Plugin\ZfcUserAuthentication');

        $authMock->expects($this->any())
                 ->method('hasIdentity')
                 ->will($this->returnValue(true));

        $authMock->expects($this->any())
                 ->method('getIdentity')
                 ->will($this->returnValue($ZfcUserMock));

        $this->controller->getPluginManager()->setService('zfcUserAuthentication', $authMock);

        parent::setUp();
    }

    public function testIndexActionCanBeAccessed()
    {
        $this->markTestIncomplete();

        // $colonyTableMock = $this->getMockBuilder('Galaxy\Table\Colony')
        //                         ->disableOriginalConstructor()
        //                         ->getMock();

        // $colonyTableMock->expects($this->once())
        //                 ->method('fetchAll')
        //                 ->will($this->returnValue(array()));

        // $serviceManager = $this->getApplicationServiceLocator();
        // $serviceManager->setAllowOverride(true);
        // $serviceManager->setService('Galaxy\Table\Colony', $colonyTableMock);

        //$this->dispatch('/galaxy');
        //$this->assertResponseStatusCode(200);
        //$this->assertModuleName('Galaxy');
        //$this->assertControllerName('Galaxy\Controller\Index');
        //$this->assertControllerClass('IndexController');
        //$this->assertMatchedRouteName('galaxy');
    }
}