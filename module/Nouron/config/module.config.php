<?php
return array(
    'controller' => array(
//        'classes' => array(
//            'album/album' => 'Album\Controller\AlbumController',
//        ),
    ),
    'router' => array(
//        'routes' => array(
//            'album' => array(
//                'type'    => 'segment',
//                'options' => array(
//                    'route'    => '/album[/:action][/:id]',
//                    'constraints' => array(
//                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
//                        'id'     => '[0-9]+',
//                    ),
//                    'defaults' => array(
//                        'controller' => 'album/album',
//                        'action'     => 'index',
//                    ),
//                ),
//            ),
//        ),
    ),
    'view_manager' => array(
        'template_path_stack' => array(
            'nouron' => __DIR__ . '/../view',
        ),
    ),
);