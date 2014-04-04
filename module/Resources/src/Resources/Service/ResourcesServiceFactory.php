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
        $db     = $serviceLocator->get('Zend\Db\Adapter\Adapter');
        $tick   = $serviceLocator->get('Nouron\Service\Tick');
        $logger = $serviceLocator->get('logger');

        $tables['resource'] = new ResourceTable($db, new Resource());
        $tables['colonyresources'] = new ColonyTable($db, new Colony());
        $tables['userresources'] = new UserTable($db, new User());

        $gateways['galaxy']    = $serviceLocator->get('Galaxy\Service\Gateway');

        $resourcesService = new ResourcesService($tick, $tables, $gateways);
        $resourcesService->setLogger($logger);
        return $resourcesService;
    }
}