<?php

namespace Resources\Controller\Plugin;

use Laminas\Mvc\Controller\Plugin\AbstractPlugin;
use Laminas\Session\Container;

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
        $gw = $sm->get('Resources\Service\ResourcesService');
        $resources = $gw->getResources()->getArrayCopy('id');
        $possessions = $gw->getPossessionsByColonyId($colonyId);
        foreach ($possessions as $resId => $poss) {
            $possessions[$resId] += $resources[$resId];
        }
        //$sm->get('logger')->log(\Laminas\Log\Logger::INFO, $possessions);
        return $possessions;
    }
}