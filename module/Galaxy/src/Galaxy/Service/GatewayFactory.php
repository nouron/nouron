<?php
namespace Galaxy\Service;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

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
        $tables['colony'] = $serviceLocator->get('Galaxy\Table\ColonyTable');
        $tables['system'] = $serviceLocator->get('Galaxy\Table\SystemTable');
        $tables['fleet']  = $serviceLocator->get('Fleet\Table\FleetTable');
        $tables['systemobject']     = $serviceLocator->get('Galaxy\Table\SystemObjectTable');
        #$tables['fleetship']        = $serviceLocator->get('Fleet\Table\FleetShipTable');
        #$tables['fleetpersonell']   = $serviceLocator->get('Fleet\Table\FleetPersonellTable');
        #$tables['fleetresearch']    = $serviceLocator->get('Fleet\Table\FleetResearchTable');
        #$tables['fleetorder']       = $serviceLocator->get('Fleet\Table\FleetOrderTable');
        #$tables['fleetresource']    = $serviceLocator->get('Fleet\Table\FleetResourceTable');
        $tables['colonybuilding']   = $serviceLocator->get('Techtree\Table\ColonyBuildingTable');
        $tables['colonyresource']   = $serviceLocator->get('Resources\Table\ColonyTable');

        //$gateways['techtree'] = $serviceLocator->get('Techtree\Service\BuildingService'); // causes circularDependancyException
        $gateway = new Gateway($tick, $tables, array(), array());
        $gateway->setLogger($logger);
        return $gateway;
    }
}