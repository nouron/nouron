<?php

namespace Galaxy\Controller\Plugin;

use Zend\Mvc\Controller\Plugin\AbstractPlugin;
use Zend\Session\Container;

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
        #$sm = $this->getController()->getServiceLocator();

        $session = new Container('selectedIds');
        $userId = $this->getController()->params()->fromRoute('uid');
        if (!$userId && isset($session->userId)) {
            $userId = $session->userId;
        }
        $session->userId = $userId;

        $systemId = $this->getController()->params()->fromRoute('sid');
        if (!$systemId && isset($session->systemId)) {
            $systemId = $session->systemId;
        }
        $session->systemId = $systemId;

        $objectId = $this->getController()->params()->fromRoute('pid');
        if (!$objectId && isset($session->objectId)) {
            $objectId = $session->objectId;
        }
        $session->objectId = $objectId;

        $colonyId = $this->getController()->params()->fromRoute('cid');
        if (!$colonyId && isset($session->colonyId)) {
            $colonyId = $session->colonyId;
        }
        $session->colonyId = $colonyId;

        $fleetId = $this->getController()->params()->fromRoute('fid');
        if (!$fleetId && isset($session->fleetId)) {
            $fleetId = $session->fleetId;
        }
        $session->fleetId = $fleetId;

        $techId = $this->getController()->params()->fromRoute('tid');
        if (!$techId && isset($session->techId)) {
            $techId = $session->techId;
        }
        $session->techId = $techId;

        return array (
            'userId'   => $userId,
            'systemId' => $systemId,
            'objectId' => $objectId,
            'colonyId' => $colonyId,
            'fleetId'  => $fleetId,
            'techId'   => $techId,
        );
    }
}