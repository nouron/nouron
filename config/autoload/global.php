<?php
/**
 * Global Configuration Override
 */

return array(
    'db' => array(
        'driver' => 'Pdo_Sqlite',
        'database' => 'data/db/nouron.db',
    ),
    'service_manager' => array(
        'factories' => array(
            // Router services (laminas-router has no Module.php, must register here)
            'HttpRouter'                              => 'Laminas\Router\Http\HttpRouterFactory',
            'Router'                                  => 'Laminas\Router\RouterFactory',
            'RoutePluginManager'                      => 'Laminas\Router\RoutePluginManagerFactory',
            \Laminas\Router\Http\TreeRouteStack::class => 'Laminas\Router\Http\HttpRouterFactory',
            \Laminas\Router\RouteStackInterface::class => 'Laminas\Router\RouterFactory',
            \Laminas\Router\RoutePluginManager::class  => 'Laminas\Router\RoutePluginManagerFactory',
            // Translator
            'translator' => 'Laminas\I18n\Translator\TranslatorServiceFactory',
            'MvcTranslator' => 'Laminas\Mvc\I18n\TranslatorFactory',
            // Database
            'Laminas\Db\Adapter\Adapter' => 'Laminas\Db\Adapter\AdapterServiceFactory',
            'logger' => function($container) {
                $logger = new \Laminas\Log\Logger();
                $logger->addWriter(new \Laminas\Log\Writer\Noop());
                return $logger;
            },
            'Core\Service\Tick' => function($container) {
                $config = $container->get('Config');
                $config = $config['tick'];
                return new \Core\Service\Tick($config);
            }
        ),
        'aliases' => array(
            // Alias old Zend-style key for backwards compat within configs
            'Zend\Db\Adapter\Adapter' => 'Laminas\Db\Adapter\Adapter',
        ),
    ),
    'tick' => array(
        'length' => 24,
        'calculation' => array (
            'start' => 3,
            'end' => 4
        ),
        'testcase' => 14479 // Tick to use in Testcases
    )
);
