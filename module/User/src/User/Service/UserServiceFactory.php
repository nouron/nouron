<?php
namespace User\Service;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;

class UserServiceFactory implements FactoryInterface
{
    /**
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @return User
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $tick = $container->get('Core\Service\Tick');

        $tables['user'] = $container->get('User\Table\UserTable');

        $service   = new UserService($tick, $tables);
        return $service;
    }
}