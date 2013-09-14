<?php
namespace INNN\Service;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

use INNN\Table\MessageTable;
use INNN\Table\MessageView;
use User\Table\UserTable;

use User\Entity\User;

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

        $tables['message'] = new MessageTable($db, new \INNN\Entity\Message());
        $tables['message_view'] = new MessageView($db, new \INNN\Entity\Message());
        $tables['user'] = new UserTable($db, new User());

        $service   = new Message($tick, $tables);
        return $service;
    }
}