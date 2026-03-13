<?php
namespace Techtree\Service;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;

class ShipServiceFactory implements FactoryInterface
{
    /**
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @return \Techtree\Service\ShipService
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $tick   = $container->get('Core\Service\Tick');
        $logger = $container->get('logger');

        $tables = array();
        $tables['ships']             = $container->get('Techtree\Table\ShipTable');
        $tables['ship_costs']        = $container->get('Techtree\Table\ShipCostTable');
        $tables['colony_buildings']  = $container->get('Techtree\Table\ColonyBuildingTable');
        $tables['colony_researches'] = $container->get('Techtree\Table\ColonyResearchTable');
        $tables['colony_ships']      = $container->get('Techtree\Table\ColonyShipTable');
        $tables['colonies']          = $container->get('Colony\Table\ColonyTable');

        $services = array();
        $services['resources'] = $container->get('Resources\Service\ResourcesService');
        $services['personell'] = $container->get('Techtree\Service\PersonellService');
        #$services['researches'] = $container->get('Techtree\Service\ResearchService');
        #$services['buildings'] = $container->get('Techtree\Service\BuildingService');
        #$services['galaxy']    = $container->get('Galaxy\Service\Gateway');

        $service = new ShipService($tick, $tables, $services);
        $service->setLogger($logger);
        return $service;
    }
}