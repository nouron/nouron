<?php
namespace INNN\Service;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class GatewayFactory implements FactoryInterface
{
    /**
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @return User
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $db = $serviceLocator->get("Zend\Db\Adapter\Adapter");
        //$tick = $serviceLocator->get('tick');
        $tick = 12345;
        $tables['message'] = new \INNN\Table\Message($db);
        $tables['event'] = new \INNN\Table\Event($db);
        #$config = $this->getConfig();
        $service   = new Gateway($tick, $tables);#, $options);
        return $service;
    }
}