<?php
namespace Techtree\Table;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class BuildingCostTableFactory implements FactoryInterface
{
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        // @var Zend\Db\Adapter\Adapter
        $adapter = $serviceLocator->get('Zend\Db\Adapter\Adapter');
        // @var Techtree\Entity\BuildingCost
        $entity  = $serviceLocator->get('Techtree\Entity\BuildingCost');
        return new BuildingCostTable($adapter, $entity);
    }
}