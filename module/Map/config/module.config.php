<?php
return array(
    'controllers' => array(
        'factories' => array(
            #'Fleet\Controller\Index' => 'Fleet\Controller\IndexControllerFactory',
            #'Fleet\Controller\Json' => 'Fleet\Controller\JsonControllerFactory',
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
                )
            )
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

        ),
    ),
);