<?php
namespace Techtree\Service;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;

class ResearchServiceFactory implements FactoryInterface
{
    /**
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @return ResearchService
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $tick   = $container->get('Core\Service\Tick');
        $logger = $container->get('logger');

        $tables = array();
        $tables['researches']        = $container->get('Techtree\Table\ResearchTable');
        $tables['research_costs']    = $container->get('Techtree\Table\ResearchCostTable');
        $tables['colony_buildings']  = $container->get('Techtree\Table\ColonyBuildingTable');
        $tables['colony_researches'] = $container->get('Techtree\Table\ColonyResearchTable');
        $tables['colonies']          = $container->get('Colony\Table\ColonyTable');

        $services = array();
        $services['resources'] = $container->get('Resources\Service\ResourcesService');
        #$services['buildings']    = $container->get('Techtree\Service\BuildingService');
        $services['personell']    = $container->get('Techtree\Service\PersonellService');

        $service = new ResearchService($tick, $tables, $services);
        $service->setLogger($logger);
        return $service;
    }
}