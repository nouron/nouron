<?php
namespace Techtree\Service;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;

class BuildingServiceFactory implements FactoryInterface
{
    /**
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @return \Techtree\Service\BuildingService
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $tick   = $container->get('Core\Service\Tick');
        $logger = $container->get('logger');

        $tables = array();

        $tables['buildings']        = $container->get('Techtree\Table\BuildingTable');
        $tables['building_costs']   = $container->get('Techtree\Table\BuildingCostTable');
        $tables['colony_buildings'] = $container->get('Techtree\Table\ColonyBuildingTable');
        $tables['colonies']         = $container->get('Colony\Table\ColonyTable');

        $services = array();
        $services['resources'] = $container->get('Resources\Service\ResourcesService');
        $services['personell'] = $container->get('Techtree\Service\PersonellService');

        $service = new BuildingService($tick, $tables, $services);
        $service->setLogger($logger);
        return $service;
    }
}