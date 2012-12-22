<?php
return array(
    'controllers' => array(
        'factories' => array(
            'Techtree\Controller\Index' => 'Techtree\Controller\IndexControllerFactory',
            'Techtree\Controller\Technology' => 'Techtree\Controller\TechnologyControllerFactory',
            'Techtree\Controller\Json' => 'Techtree\Controller\JsonControllerFactory',
        ),
    ),
    'router' => array(
        'routes' => array(
            'techtree' => array(
                'type' => 'Literal',
                'options' => array(
                    'route' => '/techtree',
                    'defaults' => array(
                        '__NAMESPACE__' => 'Techtree\Controller',
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
            'techtree' => __DIR__ . '/../view',
        ),
        'strategies' => array(
                'ViewJsonStrategy',
        ),
    ),
    'view_helpers' => array(
        'invokables' => array(
            'technologyNameLink' => 'Techtree\View\Helper\TechnologyNameLink',
        )
    ),
    'service_manager' => array(
//        'invokables' => array(
//            'Techtree\Table\Technology' => 'Techtree\Table\Technology',
//            'Techtree\Table\Possession' => 'Techtree\Table\Possession',
//        ),
        'factories' => array(
            'Techtree\Mapper\Technology' => 'Techtree\Mapper\TechnologyFactory',
            'Techtree\Mapper\Possession' => 'Techtree\Mapper\PossessionFactory',
            'Techtree\Mapper\Requirement' => 'Techtree\Mapper\RequirementFactory',
            'Techtree\Mapper\Order' => 'Techtree\Mapper\RequirementFactory',
            'Techtree\Mapper\Cost' =>  'Techtree\Mapper\CostFactory',
            'Techtree\Service\Gateway' => 'Techtree\Service\GatewayFactory',
            'Techtree\Service\GatewayTest' => 'Techtree\Service\GatewayTestFactory',
        ),
    )
);

