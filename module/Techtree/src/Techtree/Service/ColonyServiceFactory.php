<?php
namespace Techtree\Service;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class ColonyServiceFactory implements FactoryInterface
{
    /**
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @return ColonyService
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $tick   = $serviceLocator->get('Core\Service\Tick');
        $logger = $serviceLocator->get('logger');

        $sm = $serviceLocator->get('servicemanager');

        $tables = array();
        $tables['buildings']  = $serviceLocator->get('Techtree\Table\BuildingTable');
        $tables['researches'] = $serviceLocator->get('Techtree\Table\ResearchTable');
        $tables['ships']      = $serviceLocator->get('Techtree\Table\ShipTable');
        $tables['personell']  = $serviceLocator->get('Techtree\Table\PersonellTable');

        $tables['colony_buildings']  = $serviceLocator->get('Techtree\Table\ColonyBuildingTable');
        $tables['colony_researches'] = $serviceLocator->get('Techtree\Table\ColonyResearchTable');
        $tables['colony_ships']      = $serviceLocator->get('Techtree\Table\ColonyShipTable');
        $tables['colony_personell']  = $serviceLocator->get('Techtree\Table\ColonyPersonellTable');

        $services = array();

        $service = new ColonyService($tick, $tables, $services);
        $service->setLogger($logger);
        return $service;
    }
}