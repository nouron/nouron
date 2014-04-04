<?php
namespace Techtree\Service;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Techtree\Table\ActionPointTable;
use Techtree\Table\PersonellTable;
use Techtree\Table\PersonellCostTable;
use Techtree\Table\ColonyBuildingTable;
use Techtree\Table\ColonyPersonellTable;
use Techtree\Entity\ActionPoint;
use Techtree\Entity\Personell;
use Techtree\Entity\PersonellCost;
use Techtree\Entity\ColonyBuilding;
use Techtree\Entity\ColonyPersonell;


class PersonellServiceFactory implements FactoryInterface
{
    /**
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @return \Techtree\Service\PersonellService
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $db     = $serviceLocator->get("Zend\Db\Adapter\Adapter");
        $tick   = $serviceLocator->get('Nouron\Service\Tick');
        $logger = $serviceLocator->get('logger');

        $tables = array();
        $tables['personell']  = new PersonellTable($db, new Personell());
        $tables['personell_costs']  = new PersonellCostTable($db, new PersonellCost());
        $tables['colony_personell'] = new ColonyPersonellTable($db, new ColonyPersonell());
        $tables['colony_buildings'] = new ColonyBuildingTable($db, new ColonyBuilding());
        $tables['locked_actionpoints'] = new ActionPointTable($db, new ActionPoint());
        $tables['colonies'] = new \Galaxy\Table\ColonyTable($db, new \Galaxy\Entity\Colony());

        $services = array();
        $services['resources'] = $serviceLocator->get('Resources\Service\ResourcesService');
        #$services['buildings'] = $serviceLocator->get('Techtree\Service\BuildingService');
        #$services['galaxy']    = $serviceLocator->get('Galaxy\Service\Gateway');

        $service = new PersonellService($tick, $tables, $services);
        $service->setLogger($logger);
        return $service;
    }
}