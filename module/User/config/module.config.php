<?php
return array(
    'router' => array(
        'routes' => array(
            'settings' => array(
                'type' => 'Literal',
                'options' => array(
                    'route' => '/settings',
                    'defaults' => array(
                        '__NAMESPACE__' => 'User\Controller',
                        'controller' => 'User',
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
);

