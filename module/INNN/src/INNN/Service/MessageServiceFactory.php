<?php
namespace INNN\Service;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;

class MessageServiceFactory implements FactoryInterface
{
    /**
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @return MessageService
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $tick = $container->get('Core\Service\Tick');

        $tables['message'] = $container->get('INNN\Table\MessageTable');
        $tables['message_view'] = $container->get('INNN\Table\MessageView');
        $tables['user'] = $container->get('User\Table\UserTable');

        return new MessageService($tick, $tables);
    }
}