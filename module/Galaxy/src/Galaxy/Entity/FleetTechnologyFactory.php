<?php
namespace Galaxy\Entity;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class FleetTechnologyFactory implements FactoryInterface
{
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $db = $serviceLocator->get('Zend\Db\Adapter\Adapter');
        $mapper    = new FleetTechnology(new \Galaxy\Table\FleetTechnology($db));
        return $mapper;
    }
}