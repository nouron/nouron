<?php
namespace Techtree\Service;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

use Techtree\Table\BuildingTable;
use Techtree\Table\ResearchTable;
use Techtree\Table\ShipTable;
use Techtree\Table\PersonellTable;
use Techtree\Table\ColonyBuildingTable;
use Techtree\Table\ColonyResearchTable;
use Techtree\Table\ColonyShipTable;
use Techtree\Table\ColonyPersonellTable;

use Techtree\Entity\Building;
use Techtree\Entity\Research;
use Techtree\Entity\Ship;
use Techtree\Entity\Personell;
use Techtree\Entity\ColonyBuilding;
use Techtree\Entity\ColonyResearch;
use Techtree\Entity\ColonyShip;
use Techtree\Entity\ColonyPersonell;

class ColonyServiceFactory implements FactoryInterface
{
    /**
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @return \Techtree\Service\ColonyService
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $db     = $serviceLocator->get("Zend\Db\Adapter\Adapter");
        $tick   = $serviceLocator->get('Nouron\Service\Tick');
        $logger = $serviceLocator->get('logger');

        $tables = array();
        $tables['buildings']  = new BuildingTable($db, new Building());
        $tables['researches'] = new ResearchTable($db, new Research());
        $tables['ships']      = new ShipTable($db, new Ship());
        $tables['personell']  = new personellTable($db, new personell());

        $tables['colony_buildings']  = new ColonyBuildingTable($db, new ColonyBuilding());
        $tables['colony_researches'] = new ColonyResearchTable($db, new ColonyResearch());
        $tables['colony_ships']      = new ColonyShipTable($db, new ColonyShip());
        $tables['colony_personell']  = new ColonyPersonellTable($db, new ColonyPersonell());

        $services = array();

        $service = new ColonyService($tick, $tables, $services);
        $service->setLogger($logger);
        return $service;
    }
}