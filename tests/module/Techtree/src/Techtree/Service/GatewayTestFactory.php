<?php
namespace Techtree\Service;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class GatewayTestFactory implements FactoryInterface
{
    /**
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @return User
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $db = $serviceLocator->get("Zend\Db\Adapter\Adapter");
        $tick = 12345;
        $tables['technology'] = new \Techtree\Table\Technology($db);
        $tables['possession'] = new \Techtree\Table\Possession($db);
        $tables['requirement'] = new \Techtree\Table\Requirement($db);
        $tables['order'] = new \Techtree\Table\Order($db);
        $tables['cost'] = new \Techtree\Table\Cost($db);
        $service   = new Gateway($tick, $tables);
        return $service;
    }
}