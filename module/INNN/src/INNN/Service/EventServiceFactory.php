<?php
namespace INNN\Service;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;

class EventServiceFactory implements FactoryInterface
{
    /**
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @return EventService
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $tick = $container->get('Core\Service\Tick');
        $tables['event'] = $container->get('INNN\Table\EventTable');
        return new EventService($tick, $tables);
    }
}