<?php
namespace Galaxy\Entity;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class ColonyTechnologyFactory implements FactoryInterface
{
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $db = $serviceLocator->get('Zend\Db\Adapter\Adapter');
        $mapper    = new ColonyTechnology(new \Galaxy\Table\ColonyTechnology($db));
        return $mapper;
    }
}