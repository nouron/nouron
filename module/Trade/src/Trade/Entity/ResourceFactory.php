<?php
namespace Trade\Entity;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class ResourceFactory implements FactoryInterface
{
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $db = $serviceLocator->get('Zend\Db\Adapter\Adapter');
        $mapper    = new Resource(new \Trade\Table\Resource($db));
        return $mapper;
    }
}