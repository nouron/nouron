<?php
namespace Trade\Controller;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class IndexControllerFactory implements FactoryInterface
{
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $ctr = new IndexController();
        #$ctr->setGreetingService($serviceLocator->getServiceLocator()->get('greetingService'));
        return $ctr;
    }
}

