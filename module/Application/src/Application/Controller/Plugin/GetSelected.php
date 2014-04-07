<?php

namespace Application\Controller\Plugin;

use Zend\Mvc\Controller\Plugin\AbstractPlugin;

class GetSelected extends AbstractPlugin
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
        $sm = $this->getController()->getServiceLocator();

        switch (strtolower($itemType)) {
            case 'user':   $idKey = 'uid'; break;
            case 'system': $idKey = 'sid'; break;
            case 'object': $idKey = 'oid'; break;
            case 'colony': $idKey = 'cid'; break;
            case 'fleet':  $idKey = 'fid'; break;
            case 'tech':   $idKey = 'tid'; break;
            default:       $idKey = 'id';  break;
        }

        $itemType = ucfirst(strtolower($itemType));

        $itemId = $this->getController()->params()->fromRoute($idKey);
        if (!$itemId && isset($_SESSION['selected'.$itemType.'Id'])) {
            $itemId = $_SESSION['selected'.$itemType.'Id'];
        } else {
            $_SESSION['selected'.$itemType.'Id'] = $itemId;
        }
        return $itemId;
    }

    /**
     * Set the session manager
     *
     * @param  Manager $manager
     * @return GetSelected
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