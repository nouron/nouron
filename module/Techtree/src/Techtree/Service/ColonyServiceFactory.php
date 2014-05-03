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
        $tick   = $serviceLocator->get('Nouron\Service\Tick');
        $logger = $serviceLocator->get('logger');

        $tables = array();
        $tables['buildings']  = $serviceLocator->get('Techtree\Table\BuildingTable');
        $tables['researches'] = $serviceLocator->get('Techtree\Table\ResearchTable');
        $tables['ships']      = $serviceLocator->get('Techtree\Table\ShipTable');
        $tables['personell']  = $serviceLocator->get('Techtree\Table\PersonellTable');

        $tables['colony_buildings']  = $serviceLocator->get('Techtree\Table\ColonyBuildingTable');
        $tables['colony_researches'] = $serviceLocator->get('Techtree\Table\ColonyResearchTable');
        $tables['colony_ships']      = $serviceLocator->get('Techtree\Table\ColonyPersonellTable');

        $services = array();

        $service = new ColonyService($tick, $tables, $services);
        $service->setLogger($logger);
        return $service;
    }
}