<?php

namespace Test;

use Laminas\Mvc\Application;
use RuntimeException;

error_reporting(E_ALL | E_STRICT);
chdir(dirname(__DIR__));

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

        $config = include 'config/application.config.php';
        $app    = Application::init($config);
        static::$serviceManager = $app->getServiceManager();
    }

    public static function getServiceManager()
    {
        return static::$serviceManager;
    }
}

Bootstrap::init();
