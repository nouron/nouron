<?php
namespace Techtree\Table;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class ActionPointTableFactory implements FactoryInterface
{
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        // @var Zend\Db\Adapter\Adapter
        $adapter = $serviceLocator->get('Zend\Db\Adapter\Adapter');
        // @var Techtree\Entity\ActionPoint
        $entity = $serviceLocator->get('Techtree\Entity\ActionPoint');
        $table = new ActionPointTable($adapter, $entity);
        return $table;
    }
}