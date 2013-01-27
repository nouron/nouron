<?php
return array(
    'controllers' => array(
        'factories' => array(
            'Fleets\Controller\Index' => 'Fleets\Controller\IndexControllerFactory',
        ),
    ),
    'router' => array(
        'routes' => array(
            'Fleets' => array(
                'type' => 'Literal',
                'options' => array(
                    'route' => '/Fleets',
                    'defaults' => array(
                        '__NAMESPACE__' => 'Fleets\Controller',
                        'controller' => 'Index',
                        'action' => 'index',
                    ),
                ),
                'may_terminate' => true,
                'child_routes' => array(
                    'default' => array(
                        'type' => 'Segment',
                        'options' => array(
                            'route' => '/[:controller[/:action[/:id]]]',
                            'constraints' => array(
                                'controller' => '[a-zA-Z][a-zA-Z0-9_-]*',
                                'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                                'id' => '[0-9]+',
                            ),
                            'defaults' => array()
                        )
                    ),
                )
            )
        )
    ),
    'view_manager' => array(
        'template_path_stack' => array(
            'Fleets' => __DIR__ . '/../view',
        ),
        'strategies' => array(
            'ViewJsonStrategy',
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
    'service_manager' => array(
        'factories' => array(
            'Fleets\Mapper\System' => 'Fleets\Mapper\SystemFactory',
            'Fleets\Mapper\SystemObject' => 'Fleets\Mapper\SystemObjectFactory',
            'Fleets\Mapper\Colony' => 'Fleets\Mapper\ColonyFactory',
            'Fleets\Mapper\Fleet' => 'Fleets\Mapper\FleetFactory',
            'Fleets\Service\Gateway' => 'Fleets\Service\GatewayFactory',
        ),
    ),
);