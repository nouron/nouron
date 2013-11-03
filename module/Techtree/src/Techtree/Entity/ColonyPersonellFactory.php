<?php
namespace Techtree\Entity;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class ColonyPersonellFactory implements FactoryInterface
{
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $db = $serviceLocator->get('Zend\Db\Adapter\Adapter');
        $mapper    = new ColonyPersonell(new \Techtree\Table\ColonyPersonellTable($db));
        return $mapper;
    }
}