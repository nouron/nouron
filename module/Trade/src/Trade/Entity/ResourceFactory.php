<?php
namespace Trade\Entity;

use Trade\Table\Resource;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class ResourceFactory implements FactoryInterface
{
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $db = $serviceLocator->get('Zend\Db\Adapter\Adapter');
        $table = new \Trade\Table\Resource($db, new \Trade\Entity\Resource());
        $mapper = new Resource($table);
        return $mapper;
    }
}