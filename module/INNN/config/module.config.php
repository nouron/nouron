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
                            'route' => '/message[/:action]',
                            'default' => array(
                                'controller' => 'Message',
                                'action' => 'inbox'
                            )
                        )
                    ),
                    'event' => array(
                        'type' => 'Segment',
                        'options' => array(
                            'route' => '/innn/event[/:action]',
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
        'invokables' => array(
           'INNN\Table\Message' => 'INNN\Table\Message',
           'INNN\Table\Event' => 'INNN\Table\Event',
        ),
        'factories' => array(
            'INNN\Entity\Message' => 'INNN\Entity\MessageFactory',
            'INNN\Entity\Event' => 'INNN\Entity\EventFactory',
            'INNN\Service\Gateway' => 'INNN\Service\GatewayFactory',
        ),
    )
);

