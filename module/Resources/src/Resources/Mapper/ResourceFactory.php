<?php
namespace Resources\Mapper;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class ResourceFactory implements FactoryInterface
{
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        #$table     = $serviceLocator->get('Resources\Table\Resource');
        $db = $serviceLocator->get('Zend\Db\Adapter\Adapter');
        $mapper    = new Resource(new \Resources\Table\Resource($db));
        return $mapper;
    }
}