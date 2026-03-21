<?php
namespace Techtree\Entity;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Techtree\Entity\Personell;

class PersonellFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        return new Personell();
    }
}