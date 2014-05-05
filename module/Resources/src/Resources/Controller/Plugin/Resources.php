<?php

namespace Resources\Controller\Plugin;

use Zend\Mvc\Controller\Plugin\AbstractPlugin;
use Zend\Session\Container;

class Resources extends AbstractPlugin
{
    /**
     *
     * @return multitype:unknown
     */
    public function __invoke()
    {
        $colonyId = $this->getController()->getActive('colony');
        $sm = $this->getController()->getServiceLocator();
        $gw = $sm->get('Resources/Service/ResourcesService');
        $results = $gw->getResources()->getArrayCopy('id');

        $hydrator = new \Zend\Stdlib\Hydrator\ClassMethods();
        $resources = array();
        foreach ($results as $id => $entity) {
            $resources[$id] = $hydrator->extract($entity);
        }

        $possessions = $gw->getPossessionsByColonyId($colonyId);
        foreach ($possessions as $resId => $poss) {
            $possessions[$resId] += $resources[$resId];
        }
        //$sm->get('logger')->log(\Zend\Log\Logger::INFO, $possessions);
        return $possessions;
    }
}