<?php
namespace Resources\Service;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Resources\Table\ResourceTable;
use Resources\Table\ColonyTable;
use Resources\Table\UserTable;
use Resources\Entity\Resource;
use Resources\Entity\Colony;
use Resources\Entity\User;

class ResourcesServiceFactory implements FactoryInterface
{
    /**
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @return ResourcesService
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $tick   = $serviceLocator->get('Core\Service\Tick');
        $logger = $serviceLocator->get('logger');

        $tables['resource'] = $serviceLocator->get('Resources\Table\ResourceTable');
        $tables['colonyresources'] = $serviceLocator->get('Resources\Table\ColonyTable');
        $tables['userresources'] = $serviceLocator->get('Resources\Table\UserTable');

        $gateways['galaxy']    = $serviceLocator->get('Galaxy\Service\Gateway');

        $resourcesService = new ResourcesService($tick, $tables, $gateways);
        $resourcesService->setLogger($logger);
        return $resourcesService;
    }
}