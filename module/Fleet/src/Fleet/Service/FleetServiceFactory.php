<?php
namespace Fleet\Service;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\Session\Container;

class FleetServiceFactory implements FactoryInterface
{
    /**
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @return FleetService
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $tick   = $serviceLocator->get('Core\Service\Tick');
        $logger = $serviceLocator->get('logger');

        $tables = array();
        $tables['colony'] = $serviceLocator->get('Galaxy\Table\ColonyTable');
        $tables['system'] = $serviceLocator->get('Galaxy\Table\SystemTable');
        $tables['fleet']     = $serviceLocator->get('Fleet\Table\FleetTable');
        $tables['fleetship'] = $serviceLocator->get('Fleet\Table\FleetShipTable');
        $tables['fleetpersonell'] = $serviceLocator->get('Fleet\Table\FleetPersonellTable');
        $tables['fleetresearch']  = $serviceLocator->get('Fleet\Table\FleetResearchTable');
        $tables['fleetorder']     = $serviceLocator->get('Fleet\Table\FleetOrderTable');
        $tables['fleetresource']  = $serviceLocator->get('Fleet\Table\FleetResourceTable');

        $tables['ship'] = $serviceLocator->get('Techtree\Table\ShipTable');
        $tables['personell'] = $serviceLocator->get('Techtree\Table\PersonellTable');
        $tables['research']  = $serviceLocator->get('Techtree\Table\ResearchTable');

        $tables['colonyship']      = $serviceLocator->get('Techtree\Table\ColonyShipTable');
        $tables['colonypersonell'] = $serviceLocator->get('Techtree\Table\ColonyPersonellTable');
        $tables['colonyresearch']  = $serviceLocator->get('Techtree\Table\ColonyResearchTable');
        $tables['colonyresource']  = $serviceLocator->get('Resources\Table\ColonyTable');

        $service = new FleetService($tick, $tables);
        $service->setLogger($logger);
        return $service;
    }
}
