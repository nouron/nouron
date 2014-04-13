<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Application;

use Zend\Mvc\MvcEvent;
use Zend\Mvc\Router\RouteMatch;
use Zend\Mvc\ModuleRouteListener;
use Zend\Db\Adapter\Adapter as DbAdapter;

use Zend\Session\Container;

class Module
{
    protected $whitelist = array('index/index','zfcuser/login', 'user/login');

    public function onBootstrap($e)
    {
        \Locale::setDefault('de_DE');

        $app = $e->getApplication();
        $em  = $app->getEventManager();
        $sm  = $app->getServiceManager();

        $list = $this->whitelist;
        $auth = $sm->get('zfcuser_auth_service');

        $em->attach(MvcEvent::EVENT_ROUTE, function($e) use ($list, $auth) {
            $match = $e->getRouteMatch();

            // No route match, this is a 404
            if (!$match instanceof RouteMatch) {
                return;
            }

            // Route is whitelisted
            $name = $match->getMatchedRouteName();
            if (in_array($name, $list)) {
                return;
            }

            // User is authenticated
            if ($auth->hasIdentity()) {
                $session = new Container('activeIds');
                #\Zend\Debug\Debug::dump($auth->getIdentity());
                $session->userId = $auth->getIdentity()->getId();
                return;
            }

            // Redirect to the user login page, as an example
            $router   = $e->getRouter();
            $url      = $router->assemble(array(), array(
                'name' => 'zfcuser/login'
            ));

            $response = $e->getResponse();
            $response->getHeaders()->addHeaderLine('Location', $url);
            $response->setStatusCode(302);

            return $response;
        }, -100);

        $translator = $e->getApplication()
                        ->getServiceManager()
                        ->get('translator');

        \Zend\Validator\AbstractValidator::setDefaultTranslator(
            new \Zend\Mvc\I18n\Translator($translator)
        );
        $translator = $e->getApplication()->getServiceManager()->get('translator');
        #$translator->setLocale(\Locale::acceptFromHttp($_SERVER['HTTP_ACCEPT_LANGUAGE']))
        $translator->setLocale('de_DE')
                   ->setFallbackLocale('en');

        $eventManager        = $e->getApplication()->getEventManager();
        $moduleRouteListener = new ModuleRouteListener();
        $moduleRouteListener->attach($eventManager);
    }

    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    public function getAutoloaderConfig()
    {
        return array(
            'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
                ),
                'fallback_autoloader' => true,
            ),
        );
    }

#    public function getServiceConfiguration()
#    {
#        return array(
#            'factories' => array(
#                'db-adapter' =>  function($sm) {
#                    $config = $sm->get('config');
#                    $config = $config['db'];
#                    $dbAdapter = new DbAdapter($config);
#                    return $dbAdapter;
#                },
#            ),
#        );
#    }
}
