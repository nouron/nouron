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
            'events' => array(
                'type' => 'Literal',
                'options' => array(
                    'route' => '/events',
                    'defaults' => array(
                        '__NAMESPACE__' => 'INNN\Controller',
                        'controller' => 'Event',
                        'action' => 'index',
                    ),
                ),
            ),
            'messages' => array(
                'type' => 'Segment',
                'options' => array(
                    'route' => '/messages[/:action[/:id[/:type]]]',
                    'defaults' => array(
                        '__NAMESPACE__' => 'INNN\Controller',
                        'controller' => 'Message',
                        'action' => 'inbox',
                    ),
                ),
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
                'route' => 'messages',
                'order' => 1,
                'pages' => array(
                    'events' => array(
                        'label' => 'events',
                        'route' => 'events',
                        'action' => 'index'
                    ),
                    'inbox' => array(
                        'label' => 'inbox',
                        'route' => 'messages',
                        'action' => 'inbox',
                    ),
                    'new' => array(
                        'label' => 'new message',
                        'route' => 'messages',
                        'action' => 'new',
                    ),
                    'outbox' => array(
                        'label' => 'outbox',
                        'route' => 'messages',
                        'action' => 'outbox',
                        #'class' => 'secondary-nav' #doesn't work yet
                    ),
                    'archive' => array(
                        'label' => 'archive',
                        'route' => 'messages',
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

