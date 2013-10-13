<?php
namespace Techtree\Service;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

use Techtree\Table\ShipTable;
use Techtree\Table\ShipCostTable;
use Techtree\Table\ColonyBuildingTable;
use Techtree\Table\ColonyResearchTable;
use Techtree\Table\ColonyShipTable;

use Techtree\Entity\Ship;
use Techtree\Entity\ShipCost;
use Techtree\Entity\ColonyBuilding;
use Techtree\Entity\ColonyResearch;
use Techtree\Entity\ColonyShip;


class ShipServiceFactory implements FactoryInterface
{
    /**
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @return \Techtree\Service\ShipService
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $db     = $serviceLocator->get("Zend\Db\Adapter\Adapter");
        $tick   = $serviceLocator->get('Nouron\Service\Tick');
        $logger = $serviceLocator->get('logger');

        $tables = array();
        $tables['ships']  = new ShipTable($db, new Ship());
        $tables['ship_costs']  = new ShipCostTable($db, new ShipCost());
        $tables['colony_buildings'] = new ColonyBuildingTable($db, new ColonyBuilding());
        $tables['colony_researches'] = new ColonyResearchTable($db, new ColonyResearch());
        $tables['colony_ships'] = new ColonyShipTable($db, new ColonyShip());
        $tables['colonies'] = new \Galaxy\Table\ColonyTable($db, new \Galaxy\Entity\Colony());

        $services = array();
        $services['resources'] = $serviceLocator->get('Resources\Service\ResourcesService');
        $services['personell'] = $serviceLocator->get('Techtree\Service\PersonellService');
        #$services['researches'] = $serviceLocator->get('Techtree\Service\ResearchService');
        #$services['buildings'] = $serviceLocator->get('Techtree\Service\BuildingService');
        #$services['galaxy']    = $serviceLocator->get('Galaxy\Service\Gateway');

        $service = new ShipService($tick, $tables, $services);
        $service->setLogger($logger);
        return $service;
    }
}