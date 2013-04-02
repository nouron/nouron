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
                    ),
                    'technology' => array(
                        # Example-Url:  http://dev.nouron.de/techtree/technology/order/35/add
                        'type' => 'Segment',
                        'options' => array(
                            'route' => '/technology/:action/:id/:order',
                            'constraints' => array(
                                'action' => 'order',
                                'id' => '[0-9]+',
                                'order' => '[a-z]+'
                            ),
                            'defaults' => array(
                                'controller' => 'Technology',
                                'action' => 'order'
                            )
                        )
                    )
                )
            ),
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
    'navigation' => array(
        'default' => array(
            'colony' => array(
                'label' => 'colony',
                'route' => 'techtree',
                'order' => 2,
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
//        'invokables' => array(
//            'Techtree\Table\Technology' => 'Techtree\Table\Technology',
//            'Techtree\Table\Possession' => 'Techtree\Table\Possession',
//        ),
        'factories' => array(
            'Techtree\Entity\Technology' => 'Techtree\Entity\TechnologyFactory',
            'Techtree\Entity\Possession' => 'Techtree\Entity\PossessionFactory',
            'Techtree\Entity\Requirement' => 'Techtree\Entity\RequirementFactory',
            'Techtree\Entity\Order' => 'Techtree\Entity\RequirementFactory',
            'Techtree\Entity\Cost' =>  'Techtree\Entity\CostFactory',
            'Techtree\Service\Gateway' => 'Techtree\Service\GatewayFactory',
            'Techtree\Service\GatewayTest' => 'Techtree\Service\GatewayTestFactory',
        ),
    )
);

