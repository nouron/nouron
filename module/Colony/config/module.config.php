<?php
return array(
    'controllers' => array(
        'factories' => array(
            'Colony\Controller\Index' => 'Colony\Controller\IndexControllerFactory',
            'Colony\Controller\Json'  => 'Colony\Controller\JsonControllerFactory',
        ),
    ),
    'controller_plugins' => array(
        'invokables' => array(
            #'selectedIds' => 'Fleet\Controller\Plugin\SelectedIds'
        )
    ),

    # colony/123

    # colony/index
    # colony/create
    # colony/update/123

    'router' => array(
        'routes' => array(
            'colonies' => array(
                'type' => 'Segment',
                'options' => array(
                    'route' => '/colonies',
                    'defaults' => array(
                        '__NAMESPACE__' => 'Colony\Controller',
                        'controller' => 'Index',
                        'action' => 'index',
                    ),
                ),
                'may_terminate' => true,
            ),
            'colony' => array(
                'type' => 'Segment',
                'options' => array(
                    'route' => '/colony',
                    'defaults' => array(
                        '__NAMESPACE__' => 'Colony\Controller',
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
            ),
        )
    ),
    'view_manager' => array(
        'template_path_stack' => array(
            'Colony' => __DIR__ . '/../view',
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
            'Colony\Entity\Colony' => 'Colony\Entity\ColonyFactory',
            'Colony\Table\ColonyTable' => 'Colony\Table\ColonyTableFactory',
            'Colony\Service\ColonyService' => 'Colony\Service\ColonyServiceFactory'
        ),
    ),
);
