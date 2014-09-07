<?php
namespace Trade\Service;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class GatewayFactory implements FactoryInterface
{
    /**
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @return \Trade\Service\Gateway
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $tick   = $serviceLocator->get('Nouron\Service\Tick');
        $logger = $serviceLocator->get('logger');

        $tables = array(
            'researches'      => $serviceLocator->get('Trade\Table\ResearchTable'),
            'researches_view' => $serviceLocator->get('Trade\Table\ResearchView'),
            'resources'       => $serviceLocator->get('Trade\Table\ResourceTable'),
            'resources_view'  => $serviceLocator->get('Trade\Table\ResourceView')
        );

        $services = array(
            'resources' => $serviceLocator->get('Resources\Service\ResourcesService'),
            'galaxy'    => $serviceLocator->get('Galaxy\Service\Gateway')
        );

        $service = new Gateway($tick, $tables, $services);
        $service->setLogger($logger);
        return $service;
    }
}