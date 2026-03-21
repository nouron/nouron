<?php
namespace Fleet\Table;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;

class FleetResourceTableFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $adapter = $container->get('Laminas\Db\Adapter\Adapter');
        $entity = $container->get('Fleet\Entity\FleetResource');
        $table = new FleetResourceTable($adapter, $entity);
        return $table;
    }
}