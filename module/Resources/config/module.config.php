<?php
return array(
    'controllers' => array(
        'invokables' => array(
            'Resources\Controller\Json' => 'Resources\Controller\JsonController'
        )
    ),
    'router' => array(
        'routes' => array(
            'resources' => array(
                'may_terminate' => true,
                'type' => 'Segment',
                'options' => array(
                    'route' => '/resources/:controller[/:action[/:id]]',
                    'constraints' => array(
                        'controller' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id' => '[0-9]*',
                    ),
                    'defaults' => array(
                        '__NAMESPACE__' => 'Resources\Controller',
                        'controller' => 'Json',
                        'action' => 'index',
                    )
                 )
             )
         )
     ),
    'service_manager' => array(
        'factories' => array(
            'Resources\Service\Gateway' => 'Resources\Service\GatewayFactory',
        ),
    ),
    'controller_plugins' => array(
        'invokables' => array(
            'Resources' => 'Resources\Controller\Plugin\Resources',
        )
    ),
    'view_helpers' => array(
        'invokables' => array(
            'resources' => 'Resources\View\Helper\Resources',
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
);

