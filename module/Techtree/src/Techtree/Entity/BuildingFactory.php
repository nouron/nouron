<?php
namespace Techtree\Entity;

use Zend\Db\ResultSet\HydratingResultSet;
use Zend\Stdlib\Hydrator\Reflection as ReflectionHydrator;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\Db\TableGateway\TableGateway;

class BuildingFactory implements FactoryInterface
{
    /**
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @return \Techtree\Entity\Building
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $db = $serviceLocator->get('Zend\Db\Adapter\Adapter');

        $resultSetPrototype = new HydratingResultSet(
            new ReflectionHydrator, new Building()
        );
        $table  = new TableGateway('building', $db, null, $resultSetPrototype);
        $mapper = new Building($table);
        return $mapper;
    }
}