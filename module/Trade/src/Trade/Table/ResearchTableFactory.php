<?php
namespace Trade\Table;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class ResearchTableFactory implements FactoryInterface{
    public function createService(ServiceLocatorInterface $serviceLocator) {
        $adapter = $serviceLocator->get('Zend\Db\Adapter\Adapter');
        $entity = $serviceLocator->get('Trade\Entity\Research');
        $table = new ResearchTable($adapter, $entity);
        return $table;
    }
}