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
        $tick   = $serviceLocator->get('Core\Service\Tick');
        $logger = $serviceLocator->get('logger');

        $tables = array();
        $tables['system'] = $serviceLocator->get('Galaxy\Table\SystemTable');
        $tables['systemobject'] = $serviceLocator->get('Galaxy\Table\SystemObjectTable');

        $tables['colony'] = $serviceLocator->get('Colony\Table\ColonyTable');
        $tables['fleet']  = $serviceLocator->get('Fleet\Table\FleetTable');
        $tables['colonybuilding']   = $serviceLocator->get('Techtree\Table\ColonyBuildingTable');
        $tables['colonyresource']   = $serviceLocator->get('Resources\Table\ColonyTable');

        $services = array();
        #$services['colony'] = $serviceLocator->get('Colony\Service\ColonyService');

        //$gateways['techtree'] = $serviceLocator->get('Techtree\Service\BuildingService'); // causes circularDependancyException
        $gateway = new Gateway($tick, $tables, $services, array());
        $gateway->setLogger($logger);
        return $gateway;
    }
}
