<?php
namespace Trade\Service;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Trade\Table\TechnologyTable;
use Trade\Table\TechnologyView;
use Trade\Table\ResourceTable;
use Trade\Table\ResourceView;

use Trade\Entity\Technology;
use Trade\Entity\Resource;

class GatewayFactory implements FactoryInterface
{
    /**
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @return \Trade\Service\Gateway
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $db     = $serviceLocator->get("Zend\Db\Adapter\Adapter");
        $tick   = $serviceLocator->get('Nouron\Service\Tick');
        $logger = $serviceLocator->get('logger');

        $tables['technology']      = new TechnologyTable($db, new Technology());
        $tables['technology_view'] = new TechnologyView($db, new Technology());
        $tables['resources']       = new ResourceTable($db, new Resource());
        $tables['resources_view']  = new ResourceView($db, new Resource());

        $gateways['resources'] = $serviceLocator->get('Resources\Service\Gateway');
        $gateways['galaxy']    = $serviceLocator->get('Galaxy\Service\Gateway');

        $techtreeGateway = new Gateway($tick, $tables, $gateways);
        $techtreeGateway->setLogger($logger);
        return $techtreeGateway;
    }
}