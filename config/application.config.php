<?php
return array(
    'modules' => array(
        'Application',
        'ZfcBase',
        'ZfcUser',
        'ZfcAdmin',
        'User', /* based on ZfcUser */
        'ZfcRbac',
        'Nouron',
        'Galaxy',
        'Resources',
        'Techtree',
        'Trade',
        'INNN',
        /*'Fleets', /* maybe combine this with galaxy like in old version*/
        'DluTwBootstrap',
//         'DluTwBootstrapDemo'
        'ZendDeveloperTools'
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
            'navigation' => 'Zend\Navigation\Service\DefaultNavigationFactory',
        ),
    ),
);
