<?php

namespace Application\Controller\Plugin;

use Zend\Mvc\Controller\Plugin\AbstractPlugin;
use Zend\Session\SessionManager;

class GetActive extends AbstractPlugin
{
    /**
     * @var Manager
     */
    protected $session;

    /**
     *
     * @return multitype:unknown
     */
    public function __invoke($itemType)
    {
        #$sm = $this->getController()->getServiceLocator();
        switch (strtolower($itemType)) {
            case 'user':   $idKey = 'uid'; break;
            case 'colony': $idKey = 'cid'; break;
            case 'fleet':  $idKey = 'fid'; break;
            default: return null;
        }
        $itemType = ucfirst(strtolower($itemType));
        $itemId = $this->getController()->params()->fromRoute($idKey);
        if (isset($_SESSION[$itemType+'Id'])) {
            $itemId = $_SESSION[$itemType+'Id'];
        } else {
            if ($itemType == 'User') {
                // getActiveUser/getLoggedInUser
                $itemId = 3;
            } elseif ($itemType == 'Colony') {
                // getActiveColony
                $itemId = 1;
            } elseif ($itemType == 'Fleet') {
                // getActiveFleet
                $itemId = 10;
            }
        }
        $_SESSION[$itemType.'Id'] = $itemId;
        return $itemId;
    }

    /**
     * Set the session manager
     *
     * @param  SessionManager $manager
     * @return GetActive
     */
    public function setSessionManager(SessionManager $manager)
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
        if (!$this->session instanceof SessionManager) {
            $this->setSessionManager(new SessionManager());
        }
        return $this->session;
    }
}