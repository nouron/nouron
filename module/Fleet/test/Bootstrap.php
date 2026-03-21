<?php

namespace FleetTest;

use Laminas\Mvc\Application;
use RuntimeException;

error_reporting(E_ALL | E_STRICT);
chdir(dirname(dirname(dirname(__DIR__))));

/**
 * Test bootstrap, for setting up autoloading
 */
class Bootstrap
{
    protected static $serviceManager;

    public static function init()
    {
        if (!file_exists('vendor/autoload.php')) {
            throw new RuntimeException('Run composer install first.');
        }
        include_once 'vendor/autoload.php';

        $config = array(
            'module_listener_options' => array(
                'module_paths' => array(
                    './module',
                    './vendor',
                ),
                'config_glob_paths' => array(
                    'config/autoload/{,*.}{global,local}.php',
                ),
            ),
            'modules' => array('LmcUser', 'Core', 'User', 'Colony', 'Galaxy', 'Techtree', 'Resources', 'Fleet'),
        );

        $app = Application::init($config);
        static::$serviceManager = $app->getServiceManager();
    }

    public static function getServiceManager()
    {
        return static::$serviceManager;
    }
}

Bootstrap::init();
