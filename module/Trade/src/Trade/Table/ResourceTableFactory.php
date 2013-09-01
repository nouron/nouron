<?php
namespace Trade\Table;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class ResourceTableFactory implements FactoryInterface{
    public function createService(ServiceLocatorInterface $serviceLocator) {
        $adapter = $serviceLocator->get('Zend\Db\Adapter\Adapter');
        $entity = $serviceLocator->get('Trade\Entity\Resource');
        $table = new Resource($adapter, $entity);
        return $table;
    }
}