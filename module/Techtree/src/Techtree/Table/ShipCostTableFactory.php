<?php
namespace Techtree\Table;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;

class ShipCostTableFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $adapter = $container->get('Laminas\Db\Adapter\Adapter');
        $entity = $container->get('Techtree\Entity\ShipCost');
        $table = new ShipCostTable($adapter, $entity);
        return $table;
    }
}