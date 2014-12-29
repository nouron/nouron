<?php
namespace Galaxy\Controller;

use Zend\View\Model\ViewModel;
use Zend\View\Model\JsonModel;

/**
 * @method integer getActive(String $itemType)
 * @method integer getSelected(String $itemType)
 * @method array selectedIds()
 */
class SystemController extends \Core\Controller\IngameController
{
    /**
     * Show the selected system, its planetary objects and fleets in orbit..
     * If the user has no colony yet, he can select a planetary where he wants to
     * build a new colony.
     *
     * @todo set fleet move orders
     */
    public function indexAction()
    {
        $sm = $this->getServiceLocator();

        $userId = $this->getActive('user');
        #$tick     = $sm->get('Core\Service\Tick');

        $selectedIds = $this->selectedIds();
        $systemId = $selectedIds['systemId'];
        $objectId = $selectedIds['objectId'];
        $colonyId = $selectedIds['colonyId'];

        $galaxyService = $sm->get('Galaxy\Service\Gateway');
        $fleetService  = $sm->get('Fleet\Service\FleetService');
        $system = $galaxyService->getSystem($systemId);

        $config = $sm->get('Config');
        $config = $config['system_view_config'];

        $objects  = $galaxyService->getSystemObjects($systemId)->getArrayCopy('id');

        $sysCoords = array($system->getX(), $system->getY());
        $colonies = $galaxyService->getByCoordinates('colonies', $sysCoords)->getArrayCopy();
        $fleets   = $galaxyService->getByCoordinates('fleets', $sysCoords)->getArrayCopy('id');
        $fleetIds = array_keys($fleets);
        $fleetOrders = $fleetService->getFleetOrdersByFleetIds($fleetIds);

        return new ViewModel(
            array(
                'x' => $system->getX(),
                'y' => $system->getY(),
                'userId'  => $userId,
                'system'  => $system,
                'objects' => $objects,
                'colonies' => $colonies,
                'config'  => $config,
                'fleets' => $fleets,
                'fleetOrders' => $fleetOrders,
                'sid' => $systemId,
                'pid' => $objectId,
                'cid' => $colonyId,
            )
        );
    }

//     /**
//      *
//      * @param integer $planetaryId
//      */
//     private function _setNewColony($planetaryId = null)
//     {
//         $this->view->firstColoChoice = true;
//         if ( is_numeric($planetaryId) ) {
//             $this->view->planetary = $this->galaxyGateway->getSystemObject($planetaryId);
//             if ($this->params()->fromPost('confirm') == 1){
//                 $data = array(
//                     'nUser' => $this->user->nId,
//                     'nPlanetary' => $this->view->planetary->nId,
//                     'nSinceTick' => Zend_Registry::get('Tick'),
//                     // @todo: check if spot is free!
//                     'nSpot' => 0,
//                     'bHome' => 1,

//                 );
//                 $newColony = $this->galaxyGateway->createColony($data);
//                 $colonyId  = $newColony->save();

//                 $resourcesGw = new Resources_Model_Gateway();
//                 $resourcesGw->generateStartResources($colonyId);
//             }
//         }
//     }

    /**
     * @todo
     * @return JSON
     */
    public function getpathasjsonAction()
    {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        try {
            $destinationCoords = $this->params()->fromPost('coords');
            $fleetId = $this->params()->fromPost('fleetId');

            if (!is_string($destinationCoords)) {
                return new JsonModel(array(
                    'error' => 'unknown destination',
                    'destinationCoords' => $destinationCoords
                ));
            }
            if (!is_numeric($fleetId)) {
                return new JsonModel(array(
                    'error' => 'unknown fleet'
                ));
            }

            $sm = $this->getServiceLocator();
            $galaxyService = $sm->get('Galaxy\Service\Gateway');
            $fleet = $galaxyService->getFleet($fleetId);

            $coords = $fleet->getCoords();
            $destinationCoords = unserialize($destinationCoords);
            $speed  = $fleet->getTravelSpeed();
            $path   = $galaxyService->getPath($coords, $destinationCoords, $speed);

            return new JsonModel($path);
        }
        catch (\Exception $e)
        {
            return new JsonModel(array('error' => $e->getMessage()));
        }
    }

    /**
     *
     */
    public function addfleetorderAction()
    {
        try {
            $fleetId   = $this->params()->fromPost('fleetId');
            $order     = $this->params()->fromPost('order');
            $destination = $this->params()->fromPost('coords');

            $sm = $this->getServiceLocator();
            $galaxyService = $sm->get('Galaxy\Service\Gateway');
            $fleet = $galaxyService->getFleet($fleetId);

            if ( $fleet->getUserId() == $this->getActive('user') ) {
                $galaxyService->addOrder($order, $fleet, $destination);
                #$this->_flashMessenger->setNamespace('success')->addMessage('order_confirmed');
            } else {
                #$this->_flashMessenger->setNamespace('error')->addMessage('order_denied');
            }

        } catch (\Exception $e) {
            #$this->_flashMessenger->setNamespace('error')->addMessage('order_denied');

            #$this->_flashMessenger->setNamespace('error')->addMessage($e->getMessage());
        }

        $this->redirect('galaxy/system/show/');
    }
}
