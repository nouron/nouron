<?php
return array(
    'controllers' => array(
        'factories' => array(
            'User\Controller\Settings' => 'User\Controller\SettingsControllerFactory',
            'User\Controller\User'     => 'User\Controller\UserControllerFactory',
        ),
    ),
    'router' => array(
        'routes' => array(
            'user' => array(
                'type' => 'Segment',
                'options' => array(
                    'route' => '/user[/:uid]',
                    'constraints' => array(
                        'uid' => '[0-9]+',
                    ),
                    'default' => array(
                        '__NAMESPACE__' => 'User\Controller',
                        'controller' => 'User',
                        'action' => 'user',
                    )
                )
            ),
            'settings' => array(
                'type' => 'Literal',
                'options' => array(
                    'route' => '/settings',
                    'defaults' => array(
                        '__NAMESPACE__' => 'User\Controller',
                        'controller' => 'Settings',
                        'action' => 'index',
                    ),
                ),
            ),
        ),
    ),
    'navigation' => array(
        'default' => array(
            'settings' => array(
                 'label' => 'settings',
                 'route' => 'settings',
                 'order' => 10
            )
        )
    ),
    'view_manager' => array(
        'template_path_stack' => array(
            'zfc-user-mod' => __DIR__ . '/../view',
        ),
    ),
    'view_helpers' => array(
        'invokables'=> array(
            'user_name_link' => 'User\View\Helper\UserNameLink'
        )
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
    'service_manager' => array(
        'factories' => array(
            'User\Entity\User' => 'User\Entity\UserFactory',
        ),
    ),
);

