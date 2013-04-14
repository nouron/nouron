<?php
/**
 * ZfcRbac Configuration
 *
 * If you have a ./config/autoload/ directory set up for your project, you can
 * drop this config file in it and change the values as you wish.
 */
$settings = array(
    /**
     * The default role that is used if no role is found from the
     * role provider.
     */
    'anonymousRole' => 'anonymous',

    /**
     * Flag: enable or disable the routing firewall.
     */
    'firewallRoute' => false,

    /**
     * Flag: enable or disable the controller firewall.
     */
    'firewallController' => true,

    /**
     * Set the view template to use on a 403 error.
     */
    'template' => 'error/403',

    /**
     * flag: enable or disable the use of lazy-loading providers.
     */
    'enableLazyProviders' => true,

    'firewalls' => array(
        'ZfcRbac\Firewall\Route' => array(
            array('route' => 'member', 'roles' => 'admin')
        ),
    ),

    'providers' => array(
        'ZfcRbac\Provider\Generic\Role\InMemory' => array(
            'roles' => array(
                'admin',
                'player' => array('admin'),
                'guest' => array('player'),
            ),
        ),
        'ZfcRbac\Provider\Generic\Permission\InMemory' => array(
            'permissions' => array(
                'admin' => array('admin'),
            )
        ),
    ),

    /**
     * Set the identity provider to use. The identity provider must be retrievable from the
     * service locator and must implement \ZfcRbac\Identity\IdentityInterface.
     */
    'identity_provider' => 'zfcuser_auth_service' #'standard_identity'
);

$serviceManager = array(
    'factories' => array(
        'standard_identity' => function ($sm) {
                $roles = array('guest','member','admin');
                $identity = new \ZfcRbac\Identity\StandardIdentity($roles);
                return $identity;
        },
    )
);

/**
 * You do not need to edit below this line
 */
return array(
    'zfcrbac' => $settings,
    'service_manager' => $serviceManager,
);
