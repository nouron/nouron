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
                'type' => 'Literal',
                'options' => array(
                    'route' => '/trade',
                    'defaults' => array(
                        '__NAMESPACE__' => 'Trade\Controller',
                        'controller' => 'Index',
                        'action' => 'index',
                    ),
                ),
                'may_terminate' => true,
            ),
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
                'order' => 4,
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
        'factories' => array(
            'Trade\Entity\Technology' => 'Trade\Entity\TechnologyFactory',
            'Trade\Entity\Resource'   => 'Trade\Entity\ResourceFactory',
            'Trade\Service\Gateway'   => 'Trade\Service\GatewayFactory',
        ),
    )
);

