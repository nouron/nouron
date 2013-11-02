<?php
namespace Trade\Service;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Trade\Table\ResearchTable;
use Trade\Table\ResearchView;
use Trade\Table\ResourceTable;
use Trade\Table\ResourceView;

use Trade\Entity\Research;
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

        $tables['researches']      = new ResearchTable($db, new Research());
        $tables['researches_view'] = new ResearchView($db, new Research());
        $tables['resources']       = new ResourceTable($db, new Resource());
        $tables['resources_view']  = new ResourceView($db, new Resource());

        $gateways['resources'] = $serviceLocator->get('Resources\Service\ResourcesService');
        $gateways['galaxy']    = $serviceLocator->get('Galaxy\Service\Gateway');

        $techtreeGateway = new Gateway($tick, $tables, $gateways);
        $techtreeGateway->setLogger($logger);
        return $techtreeGateway;
    }
}