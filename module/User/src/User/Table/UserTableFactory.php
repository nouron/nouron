<?php
namespace User\Table;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;

class UserTableFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $adapter = $container->get('Laminas\Db\Adapter\Adapter');
        $entity = $container->get('User\Entity\User');
        $table = new UserTable($adapter, $entity);
        return $table;
    }
}