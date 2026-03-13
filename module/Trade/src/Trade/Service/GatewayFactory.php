<?php
namespace Trade\Service;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;

class GatewayFactory implements FactoryInterface
{
    /**
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @return \Trade\Service\Gateway
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $tick   = $container->get('Core\Service\Tick');
        $logger = $container->get('logger');

        $tables = array(
            'researches'      => $container->get('Trade\Table\ResearchTable'),
            'researches_view' => $container->get('Trade\Table\ResearchView'),
            'resources'       => $container->get('Trade\Table\ResourceTable'),
            'resources_view'  => $container->get('Trade\Table\ResourceView')
        );

        $services = array(
            'resources' => $container->get('Resources\Service\ResourcesService'),
            'galaxy'    => $container->get('Galaxy\Service\Gateway')
        );

        $service = new Gateway($tick, $tables, $services);
        $service->setLogger($logger);
        return $service;
    }
}