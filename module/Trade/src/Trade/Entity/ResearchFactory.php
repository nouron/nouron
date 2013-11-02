<?php
namespace Trade\Entity;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class ResearchFactory implements FactoryInterface
{
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $db = $serviceLocator->get('Zend\Db\Adapter\Adapter');
        $table = new \Trade\Table\Research($db, new \Trade\Entity\Research());
        $mapper = new Research($table);
        return $mapper;
    }
}