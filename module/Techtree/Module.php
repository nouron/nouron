<?php
namespace Techtree;

class Module
{
    const STAGES = 6;
    const GRID_COLUMNS = 10;
    const GRID_ROWS_PER_STAGE = 5;
    const GRID_HEIGHT = 1400;
    const GRID_WIDTH  = 940;
    const GRID_COLUMN_SPACE = 2;
    const GRID_ROW_SPACE = 10;

    const ADVISOR_ENGINEER_TECHID = 35;
    const ADVISOR_SCIENTIST_TECHID = 36;
    const ADVISOR_FLEETCOMMANDER_TECHID = 89;
    const ADVISOR_DIPLOMAT_TECHID = 90;
    const ADVISOR_CHIEFOFINTELLIGENCE = 94;

    public function getAutoloaderConfig()
    {
        return array(
            'Zend\Loader\ClassMapAutoloader' => array(
                __DIR__ . '/autoload_classmap.php',
            ),
            'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
                ),
            ),
        );
    }

    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }
}

