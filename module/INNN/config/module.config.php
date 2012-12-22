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
            'techtree' => array(
                'type' => 'Literal',
                'options' => array(
                    'route' => '/innn',
                    'defaults' => array(
                        '__NAMESPACE__' => 'INNN\Controller',
                        'controller' => 'Event',
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
                    )
                )
            )
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
    'service_manager' => array(
       'invokables' => array(
           'INNN\Table\Message' => 'INNN\Table\Message',
           'INNN\Table\Event' => 'INNN\Table\Event',
       ),
        'factories' => array(
            'INNN\Mapper\Message' => 'INNN\Mapper\MessageFactory',
            'INNN\Mapper\Event' => 'INNN\Mapper\EventFactory',
            'INNN\Service\Gateway' => 'INNN\Service\GatewayFactory',
        ),
    )
);

