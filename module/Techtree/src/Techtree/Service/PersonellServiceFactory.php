<?php
namespace Techtree\Service;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;

class PersonellServiceFactory implements FactoryInterface
{
    /**
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @return \Techtree\Service\PersonellService
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $tick   = $container->get('Core\Service\Tick');
        $logger = $container->get('logger');

        $tables = array();
        $tables['personell']        = $container->get('Techtree\Table\PersonellTable');
        $tables['personell_costs']  = $container->get('Techtree\Table\PersonellCostTable');
        $tables['colony_personell'] = $container->get('Techtree\Table\ColonyPersonellTable');
        $tables['colony_buildings'] = $container->get('Techtree\Table\ColonyBuildingTable');
        $tables['locked_actionpoints'] = $container->get('Techtree\Table\ActionPointTable');
        $tables['colonies'] = $container->get('Colony\Table\ColonyTable');

        $services = array();
        $services['resources'] = $container->get('Resources\Service\ResourcesService');
        #$services['buildings'] = $container->get('Techtree\Service\BuildingService');
        #$services['galaxy']    = $container->get('Galaxy\Service\Gateway');

        $service = new PersonellService($tick, $tables, $services);
        $service->setLogger($logger);
        return $service;
    }
}