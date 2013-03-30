<?php
namespace Galaxy\Service;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class GalaxyFactory implements FactoryInterface
{
    /**
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @return
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $db     = $serviceLocator->get('Zend\Db\Adapter\Adapter');
        $tick   = $serviceLocator->get('Nouron\Service\Tick');
        $logger = $serviceLocator->get('logger');

        $tables['colony'] = new \Galaxy\Table\Colony($db);
        $tables['system'] = new \Galaxy\Table\System($db);
        $tables['fleet']  = new \Galaxy\Table\Fleet($db);
        $tables['systemobject'] = new \Galaxy\Table\SystemObject($db);
        $tables['colonytechnology'] = new \Techtree\Table\Possession($db);
        #$tables['colonyresource']  = new \Resources\Table\Possession($db);

        $gateway = new Gateway($tick, $tables, array());
        $gateway->setLogger($logger);
        return $gateway;
    }
}