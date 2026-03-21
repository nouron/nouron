<?php
namespace Techtree\Entity;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;

class ActionPointFactory implements FactoryInterface
{
    /**
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @return \Techtree\Entity\Building
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        return new ActionPoint();
    }
}