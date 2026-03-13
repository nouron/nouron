<?php
return array(
    'modules' => array(
        'Laminas\Mvc\Plugin\Prg',
        'Laminas\Mvc\I18n',
        'Application',
        'LmcUser',
        /* 'ZfcAdmin', */ // No Laminas port available
        'User', /* based on LmcUser */
        /*'LmcRbacMvc',*/
        'Core',
        'Galaxy',
        'Resources',
        'Techtree',
        'Fleet',
        'Trade',
        'INNN',
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
