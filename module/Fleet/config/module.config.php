<?php
return array(
    'controllers' => array(
        'factories' => array(
            'Fleet\Controller\Index' => 'Fleet\Controller\IndexControllerFactory',
            'Fleet\Controller\Json' => 'Fleet\Controller\JsonControllerFactory',
        ),
    ),
    'controller_plugins' => array(
        'invokables' => array(
            #'selectedIds' => 'Fleet\Controller\Plugin\SelectedIds'
        )
    ),

    # fleet/123

    # fleet/index
    # fleet/create
    # fleet/update/123
    # fleet/delete/123

    'router' => array(
        'routes' => array(
            'fleets' => array(
                'type' => 'Segment',
                'options' => array(
                    'route' => '/fleets',
                    'defaults' => array(
                        '__NAMESPACE__' => 'Fleet\Controller',
                        'controller' => 'Index',
                        'action' => 'index',
                    ),
                ),
                'may_terminate' => true,
            ),
            'fleet' => array(
                'type' => 'Segment',
                'options' => array(
                    'route' => '/fleet',
                    'defaults' => array(
                        '__NAMESPACE__' => 'Fleet\Controller',
                        'controller' => 'Index',
                        'action' => 'config',
                    ),
                ),
                'may_terminate' => true,
                'child_routes' => array(
                    'config' => array(
                        'type' => 'Segment',
                        'options' => array(
                            'route' => '[/:action[/:id]]',
                            'constraints' => array(
                                'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                                'id' => '[0-9]+',
                            ),
                            'defaults' => array(
                                'controller' => 'Index',
                                'action' => 'config',
                            ),
                        ),
                    ),
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
/*                    'default' => array(
                        'type' => 'Segment',
                        'options' => array(
                            'route' => '/:controller[/:action[/:id]]',
                            'constraints' => array(
                                'controller' => '[a-zA-Z][a-zA-Z0-9_-]*',
                                'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                                'id' => '[0-9]*',
                            ),
                            'defaults' => array(
                                '__NAMESPACE__' => 'Fleet\Controller',
                                'controller' => 'Index',
                                'action' => 'index',
                            )
                        ),
                    ),*/

                )
            ),

        /*
            'fleet' => array(
                'type' => 'Segment',
                'options' => array(
                    'route' => '/fleet[/:fid[/:order]]',
                    'constraints' => array(
                        'fid' => '[0-9]+',
                        'order' => '[a-zA-Z][a-zA-Z0-9_-]*',
                    ),
                    'defaults' => array(
                        '__NAMESPACE__' => 'Fleet\Controller',
                        'controller' => 'Index',
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
                                '__NAMESPACE__' => 'Fleet\Controller',
                                'controller' => 'Index',
                                'action' => 'index',
                            )
                        ),
                    ),
                    'fleet_entities' => array(
                        'type' => 'Segment',
                        'options' => array(
                            'route' => '/:controller[/:action[/:id]]',
                            'constraints' => array(
                                'controller' => '[a-zA-Z][a-zA-Z0-9_-]*',
                                'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                                'id' => '[0-9]*',
                            ),
                            'defaults' => array(
                                '__NAMESPACE__' => 'Fleet\Controller',
                                'controller' => 'Index',
                                'action' => 'json',
                            )
                        ),

                    ),
                ),
             ),
            'fleets' => array(
                'type' => 'Literal',
                'options' => array(
                    'route' => '/fleets',
                    'defaults' => array(
                        '__NAMESPACE__' => 'Fleet\Controller',
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
                                'controller' => 'Index',
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
                                'controller' => 'Index',
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
                                'controller' => 'Index',
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
                                'controller' => 'Index',
                                'action' => 'index'
                            )
                        )
                    )
                )
             ),*/
        )
    ),
    'view_manager' => array(
        'template_path_stack' => array(
            'Fleet' => __DIR__ . '/../view',
        ),
        'strategies' => array(
            'ViewJsonStrategy',
        ),
    ),
    'view_helpers' => array(
        'invokables' => array(
            #'customHelper' => 'Galaxy\View\Helper\ColonyNameLink',
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
            'Techtree\Entity\Colony' => 'Techtree\Entity\ColonyFactory',
            'Fleet\Entity\ColonyShip' => 'Fleet\Entity\ColonyShipFactory',
            'Fleet\Entity\ColonyPersonell' => 'Fleet\Entity\ColonyPersonellFactory',
            'Fleet\Entity\ColonyResearch' => 'Fleet\Entity\ColonyResearchFactory',
            'Fleet\Entity\ResourceResearch' => 'Fleet\Entity\ColonyReResourceFactory',
            'Fleet\Entity\Fleet' => 'Fleet\Entity\FleetFactory',
            'Fleet\Entity\FleetTechnology' => 'Fleet\Entity\FleetTechnologyFactory',
            'Fleet\Entity\FleetShip' => 'Fleet\Entity\FleetShipFactory',
            'Fleet\Entity\FleetPersonell' => 'Fleet\Entity\FleetPersonellFactory',
            'Fleet\Entity\FleetResearch' => 'Fleet\Entity\FleetResearchFactory',
            'Fleet\Entity\FleetResource' => 'Fleet\Entity\FleetResourceFactory',
            'Fleet\Entity\FleetOrder' => 'Fleet\Entity\FleetOrderFactory',
            'Fleet\Service\FleetService' => 'Fleet\Service\FleetServiceFactory',
            'Galaxy\Service\Gateway' => 'Galaxy\Service\GatewayFactory',

            'Galaxy\Table\ColonyTable' => 'Galaxy\Table\ColonyTableFactory',
            'Fleet\Table\FleetTable'  => 'Fleet\Table\FleetTableFactory',
            'Fleet\Table\FleetShipTable'  => 'Fleet\Table\FleetShipTableFactory',
            'Fleet\Table\FleetPersonellTable'  => 'Fleet\Table\FleetPersonellTableFactory',
            'Fleet\Table\FleetResearchTable'  => 'Fleet\Table\FleetResearchTableFactory',
            'Fleet\Table\FleetResourceTable'  => 'Fleet\Table\FleetResourceTableFactory',
            'Fleet\Table\FleetOrderTable'  => 'Fleet\Table\FleetOrderTableFactory',
        ),
    ),
);