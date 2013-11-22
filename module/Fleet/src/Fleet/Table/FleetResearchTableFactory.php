<?php
namespace Fleet\Table;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class FleetResearchTableFactory implements FactoryInterface
{
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $adapter = $serviceLocator->get('Zend\Db\Adapter\Adapter');
        $entity = $serviceLocator->get('Fleet\Entity\FleetResearch');
        $table = new FleetResearchTable($adapter, $entity);
        return $table;
    }
}