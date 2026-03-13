<?php
namespace Techtree\Service;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;

class ColonyServiceFactory implements FactoryInterface
{
    /**
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @return ColonyService
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $tick   = $container->get('Core\Service\Tick');
        $logger = $container->get('logger');

        $tables = array();
        $tables['buildings']  = $container->get('Techtree\Table\BuildingTable');
        $tables['researches'] = $container->get('Techtree\Table\ResearchTable');
        $tables['ships']      = $container->get('Techtree\Table\ShipTable');
        $tables['personell']  = $container->get('Techtree\Table\PersonellTable');

        $tables['colony_buildings']  = $container->get('Techtree\Table\ColonyBuildingTable');
        $tables['colony_researches'] = $container->get('Techtree\Table\ColonyResearchTable');
        $tables['colony_ships']      = $container->get('Techtree\Table\ColonyShipTable');
        $tables['colony_personell']  = $container->get('Techtree\Table\ColonyPersonellTable');

        $services = array();

        $service = new ColonyService($tick, $tables, $services);
        $service->setLogger($logger);
        return $service;
    }
}