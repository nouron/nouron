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
                    'building' => array(
                        # Example-Url:  http://dev.nouron.de/techtree/tech/35
                        'type' => 'Segment',
                        'options' => array(
                            'route' => '/building/:id',
                            'constraints' => array(
                                'id' => '[0-9]+'
                            ),
                            'defaults' => array(
                                'controller' => 'Technology',
                                'action' => 'building'
                            )
                        )
                    ),
                    'research' => array(
                        # Example-Url:  http://dev.nouron.de/techtree/tech/35
                        'type' => 'Segment',
                        'options' => array(
                            'route' => '/research/:id',
                            'constraints' => array(
                                'id' => '[0-9]+'
                            ),
                            'defaults' => array(
                                'controller' => 'Technology',
                                'action' => 'research'
                            )
                        )
                    ),
                    'ship' => array(
                        # Example-Url:  http://dev.nouron.de/techtree/tech/35
                        'type' => 'Segment',
                        'options' => array(
                            'route' => '/ship/:id',
                            'constraints' => array(
                                'id' => '[0-9]+'
                            ),
                            'defaults' => array(
                                'controller' => 'Technology',
                                'action' => 'ship'
                            )
                        )
                    ),
                    'personell' => array(
                        # Example-Url:  http://dev.nouron.de/techtree/tech/35
                        'type' => 'Segment',
                        'options' => array(
                            'route' => '/personell/:id',
                            'constraints' => array(
                                'id' => '[0-9]+'
                            ),
                            'defaults' => array(
                                'controller' => 'Technology',
                                'action' => 'personell'
                            )
                        )
                    ),
                    'order' => array(
                        # Example-Url:  http://dev.nouron.de/techtree/building/35/add
                        'type' => 'Segment',
                        'options' => array(
                            'route' => '/:entitytype/:id/:order[/:ap]',
                            'constraints' => array(
                                'entitytype' => '[a-z]+',
                                'id' => '[0-9]+',
                                'order' => '[a-z]+',
                                'ap' => '[0-9]+'
                            ),
                            'defaults' => array(
                                'controller' => 'Technology',
                                'action' => 'order'
                            )
                        )
                    ),
                    // 'reposition' => array(
                    //     # Example-Url:  http://dev.nouron.de/techtree/tech/35/reposition/:row/:column
                    //     'type' => 'Segment',
                    //     'options' => array(
                    //         'route' => '/tech/:id/reposition/:row/:column',
                    //         'constraints' => array(
                    //             'action' => 'order',
                    //             'id' => '[0-9]+',
                    //             'row' => '[0-9]+',
                    //             'column' => '[0-9]+',
                    //         ),
                    //         'defaults' => array(
                    //             'controller' => 'Technology',
                    //             'action' => 'reposition'
                    //         )
                    //     )
                    // )
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
            // 'Techtree\Entity\Building' => 'Techtree\Entity\BuildingFactory',
            // 'Techtree\Entity\BuildingCost' =>  'Techtree\Entity\BuildingCostFactory',
            // 'Techtree\Entity\ColonyBuilding' => 'Techtree\Entity\ColonyBuildingFactory',
            // 'Techtree\Entity\Research' => 'Techtree\Entity\ResearchFactory',
            // 'Techtree\Entity\ResearchCost' =>  'Techtree\Entity\ResearchCostFactory',
            // 'Techtree\Entity\Ship' => 'Techtree\Entity\ShipFactory',
            // 'Techtree\Entity\Personell' => 'Techtree\Entity\PersonellFactory',
            'Resources\Service\ResourcesService' => 'Resources\Service\ResourcesServiceFactory',
            'Techtree\Service\BuildingService' => 'Techtree\Service\BuildingServiceFactory',
            'Techtree\Service\ResearchService' => 'Techtree\Service\ResearchServiceFactory',
            'Techtree\Service\ShipService' => 'Techtree\Service\ShipServiceFactory',
            'Techtree\Service\PersonellService' => 'Techtree\Service\PersonellServiceFactory',
            'Techtree\Service\ColonyService' => 'Techtree\Service\ColonyServiceFactory',
        ),
    )
);

