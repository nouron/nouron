<?php
namespace Galaxy\Service;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Galaxy\Entity\Colony;
use Galaxy\Entity\System;
use Galaxy\Entity\SystemObject;
use Galaxy\Entity\Fleet;
use Galaxy\Entity\FleetTechnology;
use Galaxy\Entity\FleetResource;
use Galaxy\Entity\FleetOrder;

use Galaxy\Table\ColonyTable;
use Galaxy\Table\SystemTable;
use Galaxy\Table\SystemObjectTable;
use Galaxy\Table\FleetTable;
use Galaxy\Table\FleetTechnologyTable;
use Galaxy\Table\FleetResourceTable;
use Galaxy\Table\FleetOrderTable;

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

        $tables['colony'] = new ColonyTable($db, new Colony());
        $tables['system'] = new SystemTable($db, new System());
        $tables['fleet']  = new FleetTable($db, new Fleet());
        $tables['systemobject']     = new SystemObjectTable($db, new SystemObject());
        $tables['fleettechnology']  = new FleetTechnologyTable($db, new FleetTechnology());
        $tables['fleetorder']       = new FleetOrderTable($db, new FleetOrder());
        $tables['fleetresource']    = new FleetResourceTable($db, new FleetResource());
        $tables['colonytechnology'] = new \Techtree\Table\PossessionTable($db, new \Techtree\Entity\Possession());
        $tables['colonyresource']   = new \Resources\Table\ColonyTable($db, new \Resources\Entity\Colony());

        //$gateways['techtree'] = $serviceLocator->get('Techtree\Service\Gateway'); // causes circularDependancyException
        $gateway = new Gateway($tick, $tables, array());
        $gateway->setLogger($logger);
        return $gateway;
    }
}