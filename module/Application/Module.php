<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Application;

use Zend\Mvc\ModuleRouteListener;
use Zend\Db\Adapter\Adapter as DbAdapter;

class Module
{
    public function onBootstrap($e)
    {
        \Locale::setDefault('de_DE');

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

    public function getServiceConfiguration()
    {
        return array(
            'factories' => array(
                'db-adapter' =>  function($sm) {
                    $config = $sm->get('config');
                    $config = $config['db'];
                    $dbAdapter = new DbAdapter($config);
                    return $dbAdapter;
                },
            ),
        );
    }
}
