<?php
namespace Resources\Service;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;
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
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $tick   = $container->get('Core\Service\Tick');
        $logger = $container->get('logger');

        $tables['resource'] = $container->get('Resources\Table\ResourceTable');
        $tables['colonyresources'] = $container->get('Resources\Table\ColonyTable');
        $tables['userresources'] = $container->get('Resources\Table\UserTable');

        $gateways['galaxy']    = $container->get('Galaxy\Service\Gateway');

        $resourcesService = new ResourcesService($tick, $tables, $gateways);
        $resourcesService->setLogger($logger);
        return $resourcesService;
    }
}