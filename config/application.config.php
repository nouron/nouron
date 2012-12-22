<?php
return array(
    'modules' => array(
        'Application',
        'ZfcBase',
        'ZfcUser',
        'ZfcUserMod',
        'Nouron',
        'Resources',
        'Techtree',
        'Galaxy',
        'DluTwBootstrap',
        'DluTwBootstrapDemo'
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
        'factories'    => array(
        ),
    ),
);
