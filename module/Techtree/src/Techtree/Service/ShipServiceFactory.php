<?php
namespace Techtree\Service;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class ShipServiceFactory implements FactoryInterface
{
    /**
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @return \Techtree\Service\ShipService
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $tick   = $serviceLocator->get('Nouron\Service\Tick');
        $logger = $serviceLocator->get('logger');

        $tables = array();
        $tables['ships']             = $serviceLocator->get('Techtree\Table\ShipTable');
        $tables['ship_costs']        = $serviceLocator->get('Techtree\Table\ShipCostTable');
        $tables['colony_buildings']  = $serviceLocator->get('Techtree\Table\ColonyBuildingTable');
        $tables['colony_researches'] = $serviceLocator->get('Techtree\Table\ColonyResearchTable');
        $tables['colony_ships']      = $serviceLocator->get('Techtree\Table\ColonyShipTable');
        $tables['colonies']          = $serviceLocator->get('Galaxy\Table\ColonyTable');

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