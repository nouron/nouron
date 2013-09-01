<?php
namespace Galaxy\Table;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class FleetTechnologyTableFactory implements FactoryInterface
{
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $adapter = $serviceLocator->get('Zend\Db\Adapter\Adapter');
        $entity = $serviceLocator->get('Galaxy\Entity\FleetTechnology');
        $table = new FleetTechnologyTable($adapter, $entity);
        return $table;
    }
}