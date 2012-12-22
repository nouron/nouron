<?php
namespace Techtree\Mapper;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class TechnologyFactory implements FactoryInterface
{
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        #$table     = $serviceLocator->get('Techtree\Table\Technology');
        $db = $serviceLocator->get('Zend\Db\Adapter\Adapter');
        $mapper    = new Technology(new \Techtree\Table\Technology($db));
        return $mapper;
    }
}