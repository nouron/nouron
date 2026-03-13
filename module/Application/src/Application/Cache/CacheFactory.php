<?php
namespace Application\Cache;

use Laminas\Cache\StorageFactory;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;

class CacheFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $config = $container->get('Config');
        $cache  = StorageFactory::factory($config['cache_objects']);
        return $cache;
    }
}