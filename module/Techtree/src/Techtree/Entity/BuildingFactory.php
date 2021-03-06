<?php
namespace Techtree\Entity;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class BuildingFactory implements FactoryInterface
{
    /**
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @return \Techtree\Entity\Building
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        return new Building();
    }
}