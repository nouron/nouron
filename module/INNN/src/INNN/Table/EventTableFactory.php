<?php
namespace INNN\Table;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class EventTableFactory implements FactoryInterface
{
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $adapter = $serviceLocator->get('Zend\Db\Adapter\Adapter');
        $entity = $serviceLocator->get('INNN\Entity\Event');
        $table = new EventTable($adapter, $entity);
        return $table;
    }
}