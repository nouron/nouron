<?php
namespace Fleet\Service;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Laminas\Session\Container;

class FleetServiceFactory implements FactoryInterface
{
    /**
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @return FleetService
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $tick   = $container->get('Core\Service\Tick');
        $logger = $container->get('logger');

        $tables = array();
        $tables['colony'] = $container->get('Colony\Table\ColonyTable');
        $tables['system'] = $container->get('Galaxy\Table\SystemTable');
        $tables['fleet']     = $container->get('Fleet\Table\FleetTable');
        $tables['fleetship'] = $container->get('Fleet\Table\FleetShipTable');
        $tables['fleetpersonell'] = $container->get('Fleet\Table\FleetPersonellTable');
        $tables['fleetresearch']  = $container->get('Fleet\Table\FleetResearchTable');
        $tables['fleetorder']     = $container->get('Fleet\Table\FleetOrderTable');
        $tables['fleetresource']  = $container->get('Fleet\Table\FleetResourceTable');

        $tables['ship'] = $container->get('Techtree\Table\ShipTable');
        $tables['personell'] = $container->get('Techtree\Table\PersonellTable');
        $tables['research']  = $container->get('Techtree\Table\ResearchTable');

        $tables['colonyship']      = $container->get('Techtree\Table\ColonyShipTable');
        $tables['colonypersonell'] = $container->get('Techtree\Table\ColonyPersonellTable');
        $tables['colonyresearch']  = $container->get('Techtree\Table\ColonyResearchTable');
        $tables['colonyresource']  = $container->get('Resources\Table\ColonyTable');

        $services = array();
        $services['colony'] = $container->get('Colony\Service\ColonyService');

        $service = new FleetService($tick, $tables, $services);
        $service->setLogger($logger);
        return $service;
    }
}
