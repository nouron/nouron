<?php
namespace Techtree\Entity;

use Zend\Db\ResultSet\HydratingResultSet;
use Zend\Stdlib\Hydrator\Reflection as ReflectionHydrator;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\Db\TableGateway\TableGateway;

class PersonellFactory implements FactoryInterface
{
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $db = $serviceLocator->get('Zend\Db\Adapter\Adapter');

        $resultSetPrototype = new HydratingResultSet(
            new ReflectionHydrator, new Personell()
        );
        $table  = new TableGateway('personell', $db, null, $resultSetPrototype);
        $mapper = new Personell($table);
        return $mapper;
    }
}