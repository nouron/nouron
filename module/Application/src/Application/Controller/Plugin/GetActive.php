<?php

namespace Application\Controller\Plugin;

use Zend\Mvc\Controller\Plugin\AbstractPlugin;
use Zend\Session\Container;

class GetActive extends AbstractPlugin
{
    /**
     *
     * @param string $itemType
     * @return integer|null
     */
    public function __invoke($itemType)
    {
        $sm = $this->getController()->getServiceLocator();
        $itemType = strtolower($itemType);
        switch ($itemType) {
            case 'user':   $idKey = 'uid'; break;
            case 'colony': $idKey = 'cid'; break;
            case 'fleet':  $idKey = 'fid'; break;
            default: return null;
        }
        #$itemType = ucfirst(strtolower($itemType));
        $itemId = $this->getController()->params()->fromRoute($idKey);

        if (!$itemId) {
            $identifier = $itemType+'Id';
            $session = new Container('activeIds');
            $itemId = $session->$identifier;
        }

        if (!$itemId) {
            if ($itemType == 'colony') {
                // getActiveColony
                $session = new Container('activeIds');
                $userId = $session->userId;
                $galaxyService = $sm->get('Galaxy\Service\Gateway');
                $colony = $galaxyService->getPrimeColony($userId);
                $itemId = $colony->getId();
            } elseif ($itemType == 'fleet') {
                // getActiveFleet
                $itemId = 10; // TODO: get real value
            } elseif ($itemType == 'user') {
                // userId has to be set in Module::onBootstrap() after checking authentification!
                throw new Exception('userId was not set!');
            } else {
                return null; //unsupported yet
            }
        }
        $session->$identifier = $itemId;
        return $itemId;
    }
}