<?php
return array(
    'controllers' => array(
        'factories' => array(
            'Galaxy\Controller\Index' => 'Galaxy\Controller\IndexControllerFactory',
            'Galaxy\Controller\Fleet' => 'Galaxy\Controller\FleetControllerFactory',
            'Galaxy\Controller\System' => 'Galaxy\Controller\SystemControllerFactory',
            'Galaxy\Controller\Json' => 'Galaxy\Controller\JsonControllerFactory',
        ),
    ),
    'controller_plugins' => array(
        'invokables' => array(
            'selectedIds' => 'Galaxy\Controller\Plugin\SelectedIds'
        )
    ),
    'router' => array(
        'routes' => array(
            'fleet' => array(
                'type' => 'Segment',
                'options' => array(
                    'route' => '/fleet[/:fid[/:order]]',
                    'constraints' => array(
                        'fid' => '[0-9]+',
                        'order' => '[a-zA-Z][a-zA-Z0-9_-]*',
                    ),
                    'defaults' => array(
                        '__NAMESPACE__' => 'Galaxy\Controller',
                        'controller' => 'Fleet',
                        'action' => 'fleet',
                    ),
                ),
                'may_terminate' => true,
                'child_routes' => array(
                    'default' => array(
                        'type' => 'Segment',
                        'options' => array(
                            'route' => '/:controller[/:action[/:id]]',
                            'constraints' => array(
                                'controller' => '[a-zA-Z][a-zA-Z0-9_-]*',
                                'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                                'id' => '[0-9]*',
                            ),
                            'defaults' => array(
                                '__NAMESPACE__' => 'Galaxy\Controller',
                                'controller' => 'Fleet',
                                'action' => 'json',
                            )
                        )
                    ),
                ),
             ),
            'fleets' => array(
                'type' => 'Literal',
                'options' => array(
                    'route' => '/fleets',
                    'defaults' => array(
                        '__NAMESPACE__' => 'Galaxy\Controller',
                        'controller' => 'Fleet',
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
                    'by_coords' => array(
                        # Example-Url:  http://dev.nouron.de/fleets/1/2
                        'type' => 'Segment',
                        'options' => array(
                            'route' => '[/:x/:y]',
                            'constraints' => array(
                                'x' => '[0-9]+',
                                'y' => '[0-9]+',
                            ),
                            'defaults' => array(
                                'controller' => 'Fleet',
                                'action' => 'index'
                            )
                        )
                     ),
                    'by_colonyid' => array(
                        # Example-Url:  http://dev.nouron.de/fleets/colony/1
                        'type' => 'Segment',
                        'options' => array(
                            'route' => '/colony/:cid',
                            'constraints' => array(
                                'cid' => '[0-9]+',
                            ),
                            'defaults' => array(
                                'controller' => 'Fleet',
                                'action' => 'index'
                            )
                        )
                    ),
                    'by_systemid' => array(
                        # Example-Url:  http://dev.nouron.de/fleets/system/1
                        'type' => 'Segment',
                        'options' => array(
                            'route' => '/system/:sid',
                            'constraints' => array(
                                'sid' => '[0-9]+',
                            ),
                            'defaults' => array(
                                'controller' => 'Fleet',
                                'action' => 'index'
                            )
                        )
                    ),
                    'by_objectid' => array(
                        # Example-Url:  http://dev.nouron.de/fleets/object/1
                        'type' => 'Segment',
                        'options' => array(
                            'route' => '/object/:pid',
                            'constraints' => array(
                                'pid' => '[0-9]+',
                            ),
                            'defaults' => array(
                                'controller' => 'Fleet',
                                'action' => 'index'
                            )
                        )
                    )
                )
             ),
            'galaxy' => array(
                'type' => 'Literal',
                'options' => array(
                    'route' => '/galaxy',
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
                    ),
                    'system' => array(
                        # Example-Url:  http://dev.nouron.de/galaxy/1/2/3
                        'type' => 'Segment',
                        'options' => array(
                            'route' => '[/:sid[/:pid[/:cid]]]',
                            'constraints' => array(
                                'sid' => '[0-9]+',
                                'pid' => '[0-9]+',
                                'cid' => '[0-9]+',
                            ),
                            'defaults' => array(
                                'controller' => 'System',
                                'action' => 'index'
                            )
                        )
                    )
                )
            ),
            'coords' => array(
                # Example-Url:  http://dev.nouron.de/2312/5412
                'type' => 'Segment',
                'options' => array(
                    'route' => '[/:x/:y]',
                    'constraints' => array(
                        'x' => '[0-9]+',
                        'y' => '[0-9]+',
                    ),
                    'defaults' => array(
                        '__NAMESPACE__' => 'Galaxy\Controller',
                        'controller' => 'System',
                        'action' => 'index'
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
    'navigation' => array(
        'default' => array(
            'galaxy' => array(
                'label' => 'galaxy',
                'route' => 'galaxy',
                'order' => 3,
                'pages' => array(
                    'galaxy' => array(
                        'label' => 'galaxy',
                        'route' => 'galaxy',
                    ),
                    'system' => array(
                        'label' => 'system',
                        'route' => 'galaxy/system',
                    ),
                    'fleets' => array(
                        'label' => 'fleets',
                        'route' => 'fleets',
                    ),
                    'fleet' => array(
                        'label' => 'fleet',
                        'route' => 'fleet',
                    )
                )
            )
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
            'Galaxy\Entity\System' => 'Galaxy\Entity\SystemFactory',
            'Galaxy\Entity\SystemObject' => 'Galaxy\Entity\SystemObjectFactory',
            'Galaxy\Entity\Colony' => 'Galaxy\Entity\ColonyFactory',
            'Galaxy\Entity\ColonyTechnology' => 'Galaxy\Entity\ColonyTechnologyFactory',
            'Galaxy\Entity\Fleet' => 'Galaxy\Entity\FleetFactory',
            'Galaxy\Entity\FleetTechnology' => 'Galaxy\Entity\FleetTechnologyFactory',
            'Galaxy\Entity\FleetResource' => 'Galaxy\Entity\FleetResourceFactory',
            'Galaxy\Service\Gateway' => 'Galaxy\Service\GatewayFactory',

            'Galaxy\Table\ColonyTable' => 'Galaxy\Table\ColonyTableFactory',
            'Galaxy\Table\FleetTable'  => 'Galaxy\Table\FleetTableFactory',
        ),
    ),

    // gameplay specific config values:
    'galaxy_view_config' => array(
        'range'  => 10000,
        'offset' => 0,
        'scale'  => 0.05,
        'systemSize' => 3
    ),
    'system_view_config' => array(
        'range'  => 100,
        'offset' => 100,
        'scale'  => 10,//15,
        'planetSize' => 10,//20,
        'slotSize' => 10,//25
    )
);