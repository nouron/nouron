<?php
use Zend\Loader\StandardAutoloader;

chdir(dirname(__DIR__));
include __DIR__ . '/../init_autoloader.php';

$loader = new StandardAutoloader();
$loader->registerNamespace('GalaxyTest', __DIR__ . '/module/GalaxyTest');
$loader->registerNamespace('TechtreeTest', __DIR__ . '/module/TechtreeTest');
$loader->register();

Zend\Mvc\Application::init(include 'config/application.config.php');