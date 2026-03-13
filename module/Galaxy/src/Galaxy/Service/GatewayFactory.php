<?php
namespace Galaxy\Service;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;

class GatewayFactory implements FactoryInterface
{
    /**
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @return
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $tick   = $container->get('Core\Service\Tick');
        $logger = $container->get('logger');

        $tables = array();
        $tables['system'] = $container->get('Galaxy\Table\SystemTable');
        $tables['systemobject'] = $container->get('Galaxy\Table\SystemObjectTable');

        $tables['colony'] = $container->get('Colony\Table\ColonyTable');
        $tables['fleet']  = $container->get('Fleet\Table\FleetTable');
        $tables['colonybuilding']   = $container->get('Techtree\Table\ColonyBuildingTable');
        $tables['colonyresource']   = $container->get('Resources\Table\ColonyTable');

        $services = array();
        #$services['colony'] = $container->get('Colony\Service\ColonyService');

        //$gateways['techtree'] = $container->get('Techtree\Service\BuildingService'); // causes circularDependancyException
        $gateway = new Gateway($tick, $tables, $services, array());
        $gateway->setLogger($logger);
        return $gateway;
    }
}
