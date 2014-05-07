<?php
namespace INNN\Table;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class MessageTableFactory implements FactoryInterface
{
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $adapter = $serviceLocator->get('Zend\Db\Adapter\Adapter');
        $entity = $serviceLocator->get('INNN\Entity\Message');
        $table = new MessageTable($adapter, $entity);
        return $table;
    }
}