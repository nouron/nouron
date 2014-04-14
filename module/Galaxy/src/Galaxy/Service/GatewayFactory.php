<?php
namespace Galaxy\Service;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Galaxy\Entity\Colony;
use Galaxy\Entity\System;
use Galaxy\Entity\SystemObject;
use Fleet\Entity\Fleet;
use Fleet\Entity\FleetShip;
use Fleet\Entity\FleetPersonell;
use Fleet\Entity\FleetResearch;
use Fleet\Entity\FleetResource;
use Fleet\Entity\FleetOrder;
use Galaxy\Table\ColonyTable;
use Galaxy\Table\SystemTable;
use Galaxy\Table\SystemObjectTable;
use Fleet\Table\FleetTable;
use Fleet\Table\FleetShipTable;
use Fleet\Table\FleetPersonellTable;
use Fleet\Table\FleetResearchTable;
use Fleet\Table\FleetResourceTable;
use Fleet\Table\FleetOrderTable;

class GatewayFactory implements FactoryInterface
{
    /**
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @return
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $db     = $serviceLocator->get('Zend\Db\Adapter\Adapter');
        $tick   = $serviceLocator->get('Nouron\Service\Tick');
        $logger = $serviceLocator->get('logger');

        $tables = array();
        $tables['colony'] = new ColonyTable($db, new Colony());
        $tables['system'] = new SystemTable($db, new System());
        $tables['fleet']  = new FleetTable($db, new Fleet());
        $tables['systemobject']     = new SystemObjectTable($db, new SystemObject());
        $tables['fleetship']        = new FleetShipTable($db, new FleetShip());
        $tables['fleetpersonell']   = new FleetPersonellTable($db, new FleetPersonell());
        $tables['fleetresearch']    = new FleetResearchTable($db, new FleetResearch());
        $tables['fleetorder']       = new FleetOrderTable($db, new FleetOrder());
        $tables['fleetresource']    = new FleetResourceTable($db, new FleetResource());
        $tables['colonybuilding']   = new \Techtree\Table\ColonyBuildingTable($db, new \Techtree\Entity\ColonyBuilding());
        $tables['colonyresource']   = new \Resources\Table\ColonyTable($db, new \Resources\Entity\Colony());

        //$gateways['techtree'] = $serviceLocator->get('Techtree\Service\BuildingService'); // causes circularDependancyException
        $gateway = new Gateway($tick, $tables, array());
        $gateway->setLogger($logger);
        return $gateway;
    }
}