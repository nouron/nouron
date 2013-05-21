<?php
return array(
    'controllers' => array(
    ),
    'service_manager' => array(
        'factories' => array(
            'Resources\Service\Gateway' => 'Resources\Service\GatewayFactory',
        ),
    ),
    'controller_plugins' => array(
        'invokables' => array(
            'Resources' => 'Resources\Controller\Plugin\Resources',
        )
    ),
    'view_helpers' => array(
        'invokables' => array(
            'resources' => 'Resources\View\Helper\Resources',
        )
    ),
);

