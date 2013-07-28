<?php
namespace Trade\Service;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

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

        $tables['technology']  = new \Trade\Table\Technology($db);
        $tables['resources']   = new \Trade\Table\Resource($db);
        $tables['resources_view']   = new \Trade\Table\ResourceView($db);

        $gateways['resources'] = $serviceLocator->get('Resources\Service\Gateway');
        $gateways['galaxy']    = $serviceLocator->get('Galaxy\Service\Gateway');

        $techtreeGateway = new Gateway($tick, $tables, $gateways);
        $techtreeGateway->setLogger($logger);
        return $techtreeGateway;
    }
}