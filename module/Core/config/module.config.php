<?php
return array(
    // general config:
    'controller' => array(),
    'router' => array(),
    'view_manager' => array(
        'template_path_stack' => array(
            'nouron' => __DIR__ . '/../view',
        ),
    ),
    'translator' => array(
        #'locale' => 'de_DE',  # local is set in onBootstrap()-method in Module.php
        'translation_file_patterns' => array(
            array(
                'type' => 'phparray',
                'base_dir' => __DIR__ . '/../language',
                'pattern'  => '%s.php',
            )
        ),
    ),
    // gameplay specific config values:
    'tick' => array(
        'length' => 24, // in hours
        'calculation' => array (
            'start' => 3, // hour of day
            'end' => 4 // hour of day
        ),
        'testcase' => 14479 // Tick to use in Testcases
    )
);