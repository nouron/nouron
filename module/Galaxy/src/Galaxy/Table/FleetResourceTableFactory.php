<?php
namespace Galaxy\Table;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class FleetResourceTableFactory implements FactoryInterface
{
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $adapter = $serviceLocator->get('Zend\Db\Adapter\Adapter');
        $entity = $serviceLocator->get('Galaxy\Entity\FleetResource');
        $table = new FleetResourceTable($adapter, $entity);
        return $table;
    }
}