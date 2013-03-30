<?php
namespace INNN\Service;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class EventFactory implements FactoryInterface
{
    /**
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @return User
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $db   = $serviceLocator->get('Zend\Db\Adapter\Adapter');
        $tick = $serviceLocator->get('Nouron\Service\Tick');

        $tables['event'] = new \INNN\Table\Event($db);

        $service   = new Event($tick, $tables);
        return $service;
    }
}