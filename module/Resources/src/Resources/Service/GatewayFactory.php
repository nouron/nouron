<?php
namespace Resources\Service;

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
        $tables['resource'] = new \Resources\Table\Resource($db);
        #$config = $this->getConfig();
        $service   = new Gateway($tick, $tables);#, $options);
        return $service;
    }
}