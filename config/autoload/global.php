<?php
/**
 * Global Configuration Override
 *
 * You can use this file for overridding configuration values from modules, etc.
 * You would place values in here that are agnostic to the environment and not
 * sensitive to security.
 *
 * @NOTE: In practice, this file will typically be INCLUDED in your source
 * control, so do not include passwords or other sensitive information in this
 * file.
 */

return array(
    'db' => array(
        'driver'         => 'Pdo',
        'dsn'            => 'mysql:dbname=nouronzf2_dev;host=localhost',
        'driver_options' => array(
            PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'UTF8\''
        ),
    ),
    'service_manager' => array(
        'factories' => array(
            'Zend\Db\Adapter\Adapter' => 'Zend\Db\Adapter\AdapterServiceFactory',
            'logger' => function($sl) {
                $logger = new \Zend\Log\Logger();
                $config = $sl->get('Config');
                if ($config['logger']['writer'] == 'ChromePhp')
                    $logger->addWriter(new \Helloworld\Log\Writer\ChromePhp());
                else
                    $logger->addWriter('FirePhp');
                return $logger;
            },
            'Nouron\Service\Tick' => function($sl) {
                $config = $sl->get('Config');
                $config = $config['tick'];
                return new \Nouron\Service\Tick($config);
            }
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
