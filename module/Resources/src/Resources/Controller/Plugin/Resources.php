<?php

namespace Resources\Controller\Plugin;

use Zend\Mvc\Controller\Plugin\AbstractPlugin;

class Resources extends AbstractPlugin
{
    /**
     * @var Manager
     */
    protected $session;

    /**
     *
     * @return multitype:unknown
     */
    public function __invoke()
    {
        $sm = $this->getController()->getServiceLocator();
        $colony_id = $sm->get('colonyId');
        $gw = $sm->get('Resources/Service/Gateway');
        $resources = $gw->getResources()->toArray('id');
        $possessions = $gw->getPossessionsByColonyId($colony_id);
        foreach ($possessions as $resId => $poss) {
            $possessions[$resId] += $resources[$resId];
        }
        //$sm->get('logger')->log(\Zend\Log\Logger::INFO, $possessions);
        return $possessions;
    }

    /**
     * Set the session manager
     *
     * @param  Manager $manager
     * @return FlashMessenger
     */
    public function setSessionManager(Manager $manager)
    {
        $this->session = $manager;
        return $this;
    }

    /**
     * Retrieve the session manager
     *
     * If none composed, lazy-loads a SessionManager instance
     *
     * @return Manager
     */
    public function getSessionManager()
    {
        if (!$this->session instanceof Manager) {
            $this->setSessionManager(new SessionManager());
        }
        return $this->session;
    }
}