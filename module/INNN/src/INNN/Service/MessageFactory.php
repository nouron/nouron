<?php
namespace INNN\Service;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class MessageFactory implements FactoryInterface
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

        $tables['message'] = new \INNN\Table\Message($db);

        $service   = new Message($tick, $tables);
        return $service;
    }
}