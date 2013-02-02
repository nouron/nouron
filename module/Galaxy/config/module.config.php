<?php
return array(
    'controllers' => array(
        'factories' => array(
            //'Galaxy\Controller\Admin' => 'Galaxy\Controller\AdminControllerFactory',
            'Galaxy\Controller\Index' => 'Galaxy\Controller\IndexControllerFactory',
//             'Galaxy\Controller\Fleet' => 'Galaxy\Controller\FleetControllerFactory',
            'Galaxy\Controller\System' => 'Galaxy\Controller\SystemControllerFactory',
//            'Galaxy\Controller\SystemObject' => 'Galaxy\Controller\SystemObjectControllerFactory',
//             'Galaxy\Controller\Json' => 'Galaxy\Controller\JsonControllerFactory',
        ),
    ),
    'controller_plugins' => array(
        'invokables' => array(
            'selectedIds' => 'Galaxy\Controller\Plugin\SelectedIds'
        )
    ),
    'router' => array(
        'routes' => array(
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
            'Galaxy\Mapper\System' => 'Galaxy\Mapper\SystemFactory',
            'Galaxy\Mapper\SystemObject' => 'Galaxy\Mapper\SystemObjectFactory',
            'Galaxy\Mapper\Colony' => 'Galaxy\Mapper\ColonyFactory',
            'Galaxy\Mapper\Fleet' => 'Galaxy\Mapper\FleetFactory',
            'Galaxy\Service\Gateway' => 'Galaxy\Service\GatewayFactory',
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