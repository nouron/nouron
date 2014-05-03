<?php
namespace Techtree\Service;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class BuildingServiceFactory implements FactoryInterface
{
    /**
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @return \Techtree\Service\BuildingService
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $tick   = $serviceLocator->get('Nouron\Service\Tick');
        $logger = $serviceLocator->get('logger');

        $tables = array();

        $tables['buildings']        = $serviceLocator->get('Techtree\Table\BuildingTable');
        $tables['building_costs']   = $serviceLocator->get('Techtree\Table\BuildingCostTable');
        $tables['colony_buildings'] = $serviceLocator->get('Techtree\Table\ColonyBuildingTable');
        $tables['colonies']         = $serviceLocator->get('Galaxy\Table\ColonyTable');

        $services = array();
        $services['resources'] = $serviceLocator->get('Resources\Service\ResourcesService');
        $services['personell'] = $serviceLocator->get('Techtree\Service\PersonellService');

        $service = new BuildingService($tick, $tables, $services);
        $service->setLogger($logger);
        return $service;
    }
}