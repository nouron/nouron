<?php
namespace Galaxy\Table;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;

class SystemObjectTableFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $adapter = $container->get('Laminas\Db\Adapter\Adapter');
        $entity = $container->get('Galaxy\Entity\SystemObject');
        $table = new SystemObjectTable($adapter, $entity);
        return $table;
    }
}