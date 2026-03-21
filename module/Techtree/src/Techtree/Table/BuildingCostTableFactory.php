<?php
namespace Techtree\Table;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;

class BuildingCostTableFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        // @var Laminas\Db\Adapter\Adapter
        $adapter = $container->get('Laminas\Db\Adapter\Adapter');
        // @var Techtree\Entity\BuildingCost
        $entity  = $container->get('Techtree\Entity\BuildingCost');
        return new BuildingCostTable($adapter, $entity);
    }
}