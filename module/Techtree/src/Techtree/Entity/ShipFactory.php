<?php
namespace Techtree\Entity;

use Zend\Db\ResultSet\HydratingResultSet;
use Zend\Stdlib\Hydrator\Reflection as ReflectionHydrator;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\Db\TableGateway\TableGateway;

class ShipFactory implements FactoryInterface
{
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $db = $serviceLocator->get('Zend\Db\Adapter\Adapter');

        $resultSetPrototype = new HydratingResultSet(
            new ReflectionHydrator, new Ship()
        );
        $table  = new TableGateway('Ship', $db, null, $resultSetPrototype);
        $mapper = new Ship($table);
        return $mapper;
    }
}