<?php
namespace Techtree\Service;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

use Techtree\Table\ResearchTable;
use Techtree\Table\ResearchCostTable;
use Techtree\Table\ColonyBuildingTable;
use Techtree\Table\ColonyResearchTable;

use Techtree\Entity\Research;
use Techtree\Entity\ResearchCost;
use Techtree\Entity\ColonyBuilding;
use Techtree\Entity\ColonyResearch;


class ResearchServiceFactory implements FactoryInterface
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
        $tables['researches']  = new ResearchTable($db, new Research());
        $tables['research_costs']  = new ResearchCostTable($db, new ResearchCost());
        $tables['colony_buildings']  = new ColonyBuildingTable($db, new ColonyBuilding());
        $tables['colony_researches'] = new ColonyResearchTable($db, new ColonyResearch());
        $tables['colonies'] = new \Galaxy\Table\ColonyTable($db, new \Galaxy\Entity\Colony());

        $services = array();
        $services['resources'] = $serviceLocator->get('Resources\Service\ResourcesService');
        #$services['buildings']    = $serviceLocator->get('Techtree\Service\BuildingService');
        $services['personell']    = $serviceLocator->get('Techtree\Service\PersonellService');

        $service = new ResearchService($tick, $tables, $services);
        $service->setLogger($logger);
        return $service;
    }
}