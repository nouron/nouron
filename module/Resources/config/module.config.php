<?php
return array(
    'controllers' => array(
    ),
    'service_manager' => array(
        'factories' => array(
            'Resources\Service\Gateway' => 'Resources\Service\GatewayFactory',
        ),
    ),
    'view_helpers' => array(
        'invokables' => array(
            'resources' => 'Resources\View\Helper\Resources',
        )
    ),
);

