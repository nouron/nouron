<?php
return array(
    'controllers' => array(
    ),
    'service_manager' => array(
//        'invokables' => array(
//            'Resources\Table\Technology' => 'Resources\Table\Technology',
//            'Resources\Table\Possession' => 'Resources\Table\Possession',
//        ),
        'factories' => array(
//             'Resources\Mapper\Technology' => 'Resources\Mapper\TechnologyFactory',
//             'Resources\Mapper\Possession' => 'Resources\Mapper\PossessionFactory',
//             'Resources\Mapper\Requirement' => 'Resources\Mapper\RequirementFactory',
//             'Resources\Mapper\Order' => 'Resources\Mapper\RequirementFactory',
//             'Resources\Mapper\Cost' =>  'Resources\Mapper\CostFactory',
            'Resources\Service\Gateway' => 'Resources\Service\GatewayFactory',
            //'Resources\Service\GatewayTest' => 'Resources\Service\GatewayTestFactory',
        ),
    )
);

