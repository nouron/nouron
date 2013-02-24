<?php

namespace Galaxy\Controller\Plugin;

use Zend\Mvc\Controller\Plugin\AbstractPlugin;

class SelectedIds extends AbstractPlugin
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
        session_start();

        $sm = $this->getController()->getServiceLocator();

        $systemId = $this->getController()->params()->fromRoute('sid');
        if (!$systemId && isset($_SESSION['systemId'])) {
            $systemId = $_SESSION['systemId'];
        }
        $_SESSION['systemId'] = $systemId;

        $objectId = $this->getController()->params()->fromRoute('pid');
        if (!$objectId && isset($_SESSION['objectId'])) {
            $objectId = $_SESSION['objectId'];
        }
        $_SESSION['objectId'] = $objectId;

        $colonyId = $this->getController()->params()->fromRoute('cid');
        if (!$colonyId && isset($_SESSION['colonyId'])) {
            $colonyId = $_SESSION['colonyId'];
        }
        $_SESSION['colonyId'] = $colonyId;

        $fleetId = $this->getController()->params()->fromRoute('fid');
        if (!$fleetId && isset($_SESSION['fleetId'])) {
            $fleetId = $_SESSION['fleetId'];
        }
        $_SESSION['fleetId'] = $fleetId;

        $techId = $this->getController()->params()->fromRoute('tid');
        if (!$techId && isset($_SESSION['techId'])) {
            $techId = $_SESSION['techId'];
        }
        $_SESSION['techId'] = $techId;

        return array (
            'systemId' => $systemId,
            'objectId' => $objectId,
            'colonyId' => $colonyId,
            'fleetId'  => $fleetId,
            'techId'   => $techId,
        );
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