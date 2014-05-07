<?php
namespace Fleet\Service;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\Session\Container;
use Fleet\Entity\Fleet;
use Fleet\Entity\FleetShip;
use Fleet\Entity\FleetPersonell;
use Fleet\Entity\FleetResearch;
use Fleet\Entity\FleetResource;
use Fleet\Entity\FleetOrder;
use Fleet\Table\FleetTable;
use Fleet\Table\FleetShipTable;
use Fleet\Table\FleetPersonellTable;
use Fleet\Table\FleetResearchTable;
use Fleet\Table\FleetResourceTable;
use Fleet\Table\FleetOrderTable;
use Galaxy\Entity\System;
use Galaxy\Entity\Colony;
use Galaxy\Table\SystemTable;
use Galaxy\Table\ColonyTable;
use Techtree\Entity\ColonyShip;
use Techtree\Entity\ColonyPersonell;
use Techtree\Entity\ColonyResearch;
#use Techtree\Entity\ColonyResource;
use Techtree\Table\ColonyShipTable;
use Techtree\Table\ColonyPersonellTable;
use Techtree\Table\ColonyResearchTable;
#use Resources\Table\ColonyResourceTable;
use Resources\Entity\Colony as ColonyResource;

class FleetServiceFactory implements FactoryInterface
{
    /**
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @return FleetService
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $tick   = $serviceLocator->get('Nouron\Service\Tick');
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

        $service = new FleetService($tick, $tables, array());
        $service->setLogger($logger);
        $session = new Container('activeIds');
        if (isset($session->fleetId)) {
            $service->setFleetId($session->fleetId);
        }
        return $service;
    }
}
