<?php
return array(
    'controllers' => array(
        'factories' => array(
            'Trade\Controller\Index' => 'Trade\Controller\IndexControllerFactory',
        ),
    ),
    'router' => array(
        'routes' => array(
            'trade' => array(
                'type' => 'Segment',
                'options' => array(
                    'route' => '/trade[/:action][/page/:page]',
                    'constraints' => array(
                        'type'   => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                    ),
                    'defaults' => array(
                        '__NAMESPACE__' => 'Trade\Controller',
                        'controller' => 'index',
                        'action' => 'resources',
                        'page' => 1
                    ),
                ),
                'may_terminate' => true
            )
        )
    ),
    'view_manager' => array(
        'template_path_stack' => array(
            'trade' => __DIR__ . '/../view',
        ),
        'strategies' => array(
            'ViewJsonStrategy',
        ),
    ),
    'navigation' => array(
        'default' => array(
            'trade' => array(
                'label' => 'trade',
                'route' => 'trade',
                'action' => 'resources',
                'order' => 4,
                'pages' => array(
                    'resources' => array(
                        'label' => 'resources',
                        'route' => 'trade',
                        'action'=> 'resources'
                    ),
                    'researches' => array(
                        'label' => 'researches',
                        'route' => 'trade',
                        'action'=> 'researches'
                    ),
                )
            )
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
//         'invokables' => array(
//             'Trade\Entity\Resource' => 'Trade\Entity\
//         ),
        'factories' => array(
            'Trade\Entity\Research' => 'Trade\Entity\ResearchFactory',
            'Trade\Entity\Resource' => 'Trade\Entity\ResourceFactory',
            'Trade\Service\Gateway' => 'Trade\Service\GatewayFactory',
            'Trade\Table\Resource'  => 'Trade\Table\ResourceFactory',
        ),
    )
);

