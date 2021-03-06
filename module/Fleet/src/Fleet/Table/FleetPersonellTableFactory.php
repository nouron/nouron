<?php
namespace Fleet\Table;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class FleetPersonellTableFactory implements FactoryInterface
{
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $adapter = $serviceLocator->get('Zend\Db\Adapter\Adapter');
        $entity = $serviceLocator->get('Fleet\Entity\FleetPersonell');
        $table = new FleetPersonellTable($adapter, $entity);
        return $table;
    }
}