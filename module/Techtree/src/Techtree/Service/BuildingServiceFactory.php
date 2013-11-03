<?php
namespace Techtree\Service;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

use Techtree\Table\BuildingTable;
use Techtree\Table\BuildingCostTable;
use Techtree\Table\ColonyBuildingTable;

use Techtree\Entity\Building;
use Techtree\Entity\BuildingCost;
use Techtree\Entity\ColonyBuilding;


class BuildingServiceFactory implements FactoryInterface
{
    /**
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @return \Techtree\Service\BuildingService
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $db     = $serviceLocator->get("Zend\Db\Adapter\Adapter");
        $tick   = $serviceLocator->get('Nouron\Service\Tick');
        $logger = $serviceLocator->get('logger');

        $tables = array();
        $tables['buildings']  = new BuildingTable($db, new Building());
        $tables['building_costs']  = new BuildingCostTable($db, new BuildingCost());
        $tables['colony_buildings'] = new ColonyBuildingTable($db, new ColonyBuilding());
        $tables['colonies'] = new \Galaxy\Table\ColonyTable($db, new \Galaxy\Entity\Colony());

        $services = array();
        $services['resources'] = $serviceLocator->get('Resources\Service\ResourcesService');
        $services['personell'] = $serviceLocator->get('Techtree\Service\PersonellService');

        $service = new BuildingService($tick, $tables, $services);
        $service->setLogger($logger);
        return $service;
    }
}