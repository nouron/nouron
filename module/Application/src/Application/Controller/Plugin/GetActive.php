<?php

namespace Application\Controller\Plugin;

use Zend\Mvc\Controller\Plugin\AbstractPlugin;
use Zend\Session\Container;

class GetActive extends AbstractPlugin
{
    /**
     * The GetActive-Plugin should be the central point to get the current
     * active item id which BELONGS to the user!
     * This is in contrast to the GetSelected-Plugin which returns the currently
     * selected item id which NOT necessarily belongs to the user!
     *
     * how this works:
     * - first check if url parameter gives the current item id (but this must
     *   be checked first to get sure it is a property of the user!)
     * - if this fails we try to get the item id from the session
     * - if this fails we try to get the default item for given item type
     *
     * @param string $itemType
     * @return integer|null
     */
    public function __invoke($itemType)
    {
        if (strtolower($itemType) == 'user') {
            // handling user here is just a shortcut to avoid errors
            $session = new Container('activeIds');
            return $session->userId;
        }

        // first we try to get item id from url param and check if it is user property
        $itemId = $this->_getItemIdFromUrlParameterAndCheckOwner($itemType);

        // ok, item id not found or given id is not property of user
        // now look in session data:
        if (empty($itemId)) {
            $itemId = $this->_getItemIdFromSession($itemType);
        }

        // ok, not found in session too
        // now get default cases:
        if (empty($itemId)) {
            $itemId = $this->_getDefaultItemId($itemType);
        }

        if (!empty($itemId)) {
            // store found id in session
            $session = new Container('activeIds');
            $identifier = strtolower($itemType)+'Id';
            $session->$identifier = $itemId;
        }

        return $itemId;
    }

    /**
     * @param string $itemType
     * @return int|null
     */
    private function _getItemIdFromUrlParameterAndCheckOwner($itemType)
    {
        $session = new Container('activeIds');
        $userId = $session->userId;
        $sm = $this->getController()->getServiceLocator();
        switch (strtolower($itemType)) {
            case 'colony':
                $galaxyService = $sm->get('Galaxy\Service\Gateway');
                $colonyId = $this->getController()->params()->fromRoute('cid');
                if ($colonyId && $galaxyService->checkColonyOwner($colonyId, $userId)) {
                    return $colonyId;
                }
                break;
            case 'fleet':
                $fleetService = $sm->get('Fleet\Service\FleetService');
                $fleetId = $this->getController()->params()->fromRoute('fid');
                if ($fleetId && $fleetService->checkFleetOwner($fleetId, $userId)) {
                    return $fleetId;
                }
                break;
            default: return null;
        }
    }

    /**
     * @param string $itemType
     * @return int|null
     */
    private function _getItemIdFromSession($itemType)
    {
        $session = new Container('activeIds');
        switch (strtolower($itemType)) {
            case 'colony':
                if ($session->colonyId) {
                    return $session->colonyId;
                }
                break;
            case 'fleet':
                if ($session->fleetId) {
                    return $session->fleetId;
                }
                break;
            default:
                return null;
        }
    }

    /**
     * @param string $itemType
     * @return int|null
     */
    private function _getDefaultItemId($itemType)
    {
        $sm = $this->getController()->getServiceLocator();
        $session = new Container('activeIds');
        $userId = $session->userId;
        switch (strtolower($itemType)) {
            case 'colony':
                // getActiveColony
                $galaxyService = $sm->get('Galaxy\Service\Gateway');
                $colony = $galaxyService->getPrimeColony($userId);
                return $colony->getId();
            case 'fleet':
                // getActiveFleet
                $fleetService = $sm->get('Fleet\Service\FleetService');
                $fleet = $fleetService->getFleetsByUserId($userId)->current();
                return $fleet ? $fleet->getId() : null;
            default:
                return null;
        }
    }
}