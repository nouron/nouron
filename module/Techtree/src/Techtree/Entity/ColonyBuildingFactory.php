<?php
namespace Techtree\Entity;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class ColonyBuildingFactory implements FactoryInterface
{
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $db = $serviceLocator->get('Zend\Db\Adapter\Adapter');
        $mapper    = new ColonyBuilding(new \Techtree\Table\ColonyBuildingTable($db));
        return $mapper;
    }
}