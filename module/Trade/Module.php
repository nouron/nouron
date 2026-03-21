<?php
namespace Trade;

use Laminas\ModuleManager\Feature\AutoloaderProviderInterface;
use Laminas\ModuleManager\Feature\ConfigProviderInterface;

class Module implements AutoloaderProviderInterface, ConfigProviderInterface
{

    public function onBootstrap($e)
    {
        \Locale::setDefault('de_DE');
        $sm = $e->getApplication()->getServiceManager();
        $translator = $sm->get('translator');

        \Laminas\Validator\AbstractValidator::setDefaultTranslator(
            new \Laminas\Mvc\I18n\Translator($translator)
        );
    }

    public function getAutoloaderConfig()
    {
        return array(
            'Laminas\Loader\ClassMapAutoloader' => array(
                __DIR__ . '/autoload_classmap.php',
            ),
            'Laminas\Loader\StandardAutoloader' => array(
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

