<?php
namespace Trade\Entity;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class TechnologyFactory implements FactoryInterface
{
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $db = $serviceLocator->get('Zend\Db\Adapter\Adapter');
        $table = new \Trade\Table\Technology($db, new \Trade\Entity\Technology());
        $mapper = new Technology($table);
        return $mapper;
    }
}