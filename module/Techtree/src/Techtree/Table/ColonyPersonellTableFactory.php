<?php
namespace Techtree\Table;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class ColonyPersonellTableFactory implements FactoryInterface
{
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $adapter = $serviceLocator->get('Zend\Db\Adapter\Adapter');
        $entity = $serviceLocator->get('Techtree\Entity\ColonyPersonell');
        $table = new ColonyPersonellTable($adapter, $entity);
        return $table;
    }
}