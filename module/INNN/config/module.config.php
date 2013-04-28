<?php
return array(
    'controllers' => array(
        'factories' => array(
            'INNN\Controller\Event' => 'INNN\Controller\EventControllerFactory',
            'INNN\Controller\Message' => 'INNN\Controller\MessageControllerFactory',
            'INNN\Controller\Json' => 'INNN\Controller\JsonControllerFactory',
        ),
    ),
    'router' => array(
        'routes' => array(
            'innn' => array(
                'type' => 'Literal',
                'options' => array(
                    'route' => '/innn',
                    'defaults' => array(
                        '__NAMESPACE__' => 'INNN\Controller',
                        'controller' => 'Message',
                        'action' => 'inbox',
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
                    'message' => array(
                        'type' => 'Segment',
                        'options' => array(
                            'route' => '/message[/:action[/:id[/:type]]]',
                            'constraints' => array(
                                'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                                'id' => '[0-9]+',
                                'type' => '[a-zA-Z][a-zA-Z0-9_-]*',
                            ),
                            'default' => array(
                                'controller' => 'Message',
                                'action' => 'inbox'
                            )
                        )
                    ),
                    'event' => array(
                        'type' => 'Segment',
                        'options' => array(
                            'route' => '/event[/:action]',
                            'default' => array(
                                'controller' => 'Event',
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
            'innn' => __DIR__ . '/../view',
        ),
        'strategies' => array(
            'ViewJsonStrategy',
        ),
    ),
    'navigation' => array(
        'default' => array(
            'innn' => array(
                'label' => 'innn',
                'route' => 'innn/event',
                'order' => 1,
                'pages' => array(
                    'events' => array(
                        'label' => 'events',
                        'route' => 'innn/event'
                    ),
                    'inbox' => array(
                        'label' => 'inbox',
                        'route' => 'innn/message',
                        'action' => 'inbox',
                    ),
                    'new' => array(
                        'label' => 'new message',
                        'route' => 'innn/message',
                        'action' => 'new',
                    ),
                    'outbox' => array(
                        'label' => 'outbox',
                        'route' => 'innn/message',
                        'action' => 'outbox',
                        #'class' => 'secondary-nav' #doesn't work yet
                    ),
                    'archive' => array(
                        'label' => 'archive',
                        'route' => 'innn/message',
                        'action' => 'archive',
                        #'class' => 'secondary-nav'
                    )
                )
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
//         'invokables' => array(
//            'INNN\Table\Message' => 'INNN\Table\Message',
//            'INNN\Table\MessageView' => 'INNN\Table\MessageView',
//            'INNN\Table\Event'   => 'INNN\Table\Event',
//         ),
        'factories' => array(
            'INNN\Entity\Message'  => 'INNN\Entity\MessageFactory',
            'INNN\Entity\Event'    => 'INNN\Entity\EventFactory',
            'INNN\Service\Message' => 'INNN\Service\MessageFactory',
            'INNN\Service\Event'   => 'INNN\Service\EventFactory',
        ),
    )
);

