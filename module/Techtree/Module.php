<?php
namespace Techtree;

use Zend\ModuleManager\Feature\AutoloaderProviderInterface;
use Zend\ModuleManager\Feature\ConfigProviderInterface;

class Module implements AutoloaderProviderInterface, ConfigProviderInterface
{
    public function onBootstrap($e)
    {
        $sm = $e->getApplication()->getServiceManager();

        \Locale::setDefault('de_DE');
        $translator = $e->getApplication()
                        ->getServiceManager()
                        ->get('translator');

        \Zend\Validator\AbstractValidator::setDefaultTranslator(
            new \Zend\Mvc\I18n\Translator($translator)
        );

        $em = $e->getApplication()->getEventManager();
    }

    public function getAutoloaderConfig()
    {
        return array(
            'Zend\Loader\ClassMapAutoloader' => array(
                __DIR__ . '/autoload_classmap.php',
            ),
            'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
                ),
            ),
        );
    }

    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }
}

