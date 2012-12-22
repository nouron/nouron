<?php
return array(
    'controllers' => array(
        'factories' => array(
            //'Galaxy\Controller\Admin' => 'Galaxy\Controller\AdminControllerFactory',
            'Galaxy\Controller\Index' => 'Galaxy\Controller\IndexControllerFactory',
//             'Galaxy\Controller\Fleet' => 'Galaxy\Controller\FleetControllerFactory',
//             'Galaxy\Controller\System' => 'Galaxy\Controller\SystemControllerFactory',
//             'Galaxy\Controller\Systemobject' => 'Galaxy\Controller\SystemObjectControllerFactory',
//             'Galaxy\Controller\Json' => 'Galaxy\Controller\JsonControllerFactory',
        ),
    ),
    'router' => array(
        'routes' => array(
            'galaxy' => array(
                'type' => 'Literal',
                'options' => array(
                    'route' => '/Galaxy',
                    'defaults' => array(
                        '__NAMESPACE__' => 'Galaxy\Controller',
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
                    )
                )
            )
        )
    ),
    'view_manager' => array(
        'template_path_stack' => array(
            'Galaxy' => __DIR__ . '/../view',
        ),
        'strategies' => array(
                'ViewJsonStrategy',
        ),
    ),
    'view_helpers' => array(
        'invokables' => array(
            'customHelper' => 'Galaxy\View\Helper\ColonyNameLink',
        )
    ),
    'service_manager' => array(
        'factories' => array(
            'Galaxy\Mapper\System' => 'Galaxy\Mapper\SystemFactory',
            'Galaxy\Mapper\Systemobject' => 'Galaxy\Mapper\SystemobjectFactory',
            'Galaxy\Mapper\Colony' => 'Galaxy\Mapper\ColonyFactory',
            'Galaxy\Mapper\Fleet' => 'Galaxy\Mapper\FleetFactory',
            'Galaxy\Service\Gateway' => 'Galaxy\Service\GatewayFactory',
        ),
    )
);