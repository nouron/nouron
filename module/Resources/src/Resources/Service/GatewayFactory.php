<?php
namespace Resources\Service;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class GatewayFactory implements FactoryInterface
{
    /**
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @return User
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $db     = $serviceLocator->get('Zend\Db\Adapter\Adapter');
        $tick   = $serviceLocator->get('Nouron\Service\Tick');
        $logger = $serviceLocator->get('logger');

        $tables['resource'] = new \Resources\Table\Resource($db);
        $tables['colonyresources'] = new \Resources\Table\Colony($db);
        $tables['userresources'] = new \Resources\Table\User($db);

        $gateways['galaxy']    = $serviceLocator->get('Galaxy\Service\Gateway');

        $resourcesGateway = new Gateway($tick, $tables, $gateways);
        $resourcesGateway->setLogger($logger);
        return $resourcesGateway;
    }
}