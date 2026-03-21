<?php
namespace Resources\Table;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;

class ColonyTableFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $adapter = $container->get('Laminas\Db\Adapter\Adapter');
        $entity = $container->get('Resources\Entity\Colony');
        return new ColonyTable($adapter, $entity);
    }
}