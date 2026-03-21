<?php
namespace Colony\Service;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;

class ColonyServiceFactory implements FactoryInterface
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
        $tables['colony']       = $container->get('Colony\Table\ColonyTable');
        $tables['systemobject'] = $container->get('Galaxy\Table\SystemObjectTable');

        $gateway = new ColonyService($tick, $tables, array(), array());
        $gateway->setLogger($logger);
        return $gateway;
    }
}