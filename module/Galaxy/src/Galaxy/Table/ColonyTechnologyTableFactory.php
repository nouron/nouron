<?php
namespace Galaxy\Table;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class ColonyTechnologyTableFactory implements FactoryInterface
{
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $adapter = $serviceLocator->get('Zend\Db\Adapter\Adapter');
        $entity = $serviceLocator->get('Galaxy\Entity\ColonyTechnology');
        $table = new ColonyTechnologyTable($adapter, $entity);
        return $table;
    }
}