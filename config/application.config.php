<?php
return array(
    'modules' => array(
        'Laminas\Db',
        'Laminas\Router',
        'Laminas\Paginator',
        'Laminas\Mvc\Plugin\FlashMessenger',
        'Laminas\Mvc\Plugin\Prg',
        'Laminas\Filter',
        'Laminas\Hydrator',
        'Laminas\I18n',
        'Laminas\InputFilter',
        'Laminas\Validator',
        'Laminas\Form',
        'Laminas\Cache',
        'Laminas\Log',
        'Laminas\Session',
        'Laminas\Mvc\I18n',
        'Laminas\Navigation',
        'Laminas\Cache\Storage\Adapter\Filesystem',
        'Application',
        'LmcUser',
        /* 'ZfcAdmin', */ // No Laminas port available
        'User', /* based on LmcUser */
        /*'LmcRbacMvc',*/
        'Core',
        'Colony',
        'Galaxy',
        'Resources',
        'Techtree',
        'Fleet',
        'Trade',
        'INNN',
        'Map',
//        'ZendDeveloperTools'
    ),
    'module_listener_options' => array(
        'config_glob_paths'    => array(
            'config/autoload/{,*.}{global,local}.php',
        ),
        'module_paths' => array(
            './module',
            './vendor',
        ),
    ),
    'service_manager' => array(
        'use_defaults' => true,
        'factories' => array(
            'navigation' => 'Laminas\Navigation\Service\DefaultNavigationFactory',
        ),
    ),
);
