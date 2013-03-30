<?php
namespace Galaxy\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
//use Galaxy\Service\Gateway;

class SystemController extends AbstractActionController
{
//     function init()
//     {
//         parent::init();

//         $ajaxContext = $this->_helper->getHelper('AjaxContext');
//         $ajaxContext->addActionContext('getpathasjson', 'json')
//                     ->initContext();

//         $this->view->heading = 'Galaxie ' . $this->view->hintBox(3);
//     }

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

        $userId = 3;

        $sm->setService('colonyId', 0); // TODO: get colonyId via controller plugin or session
        $sm->setService('systemId', 1); // TODO: get systemId via controller plugin or session

        $tick     = $sm->get('Nouron\Service\Tick');

        $selectedIds = $this->selectedIds();
        $systemId = $selectedIds['systemId'];
        $objectId = $selectedIds['objectId'];
        $colonyId = $selectedIds['colonyId'];

        $gw = $sm->get('Galaxy\Service\Gateway');
        $system = $gw->getSystem($systemId);

        $config = $sm->get('Config');
        $config = $config['system_view_config'];

        $objects  = $gw->getSystemObjects($systemId)->toArray('id');

        $sysCoords = array($system['x'], $system['y']);
        $colonies = $gw->getByCoordinates('colonies', $sysCoords)->toArray();
        $fleets   = $gw->getByCoordinates('fleets', $sysCoords)->toArray('id');
        $fleetIds = array_keys($fleets);
        $fleetOrders = $gw->getFleetOrdersByFleetIds($fleetIds);

        return new ViewModel(
            array(
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
//             if ($this->_params->confirm == 1){
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
            $destinationCoords = $this->_params->coords;
            $fleetId = $this->_params->fleetId;

            if (!is_string($destinationCoords)) return $this->_helper->json(array('error' => 'unknown destination','destinationCoords' => $destinationCoords));
            if (!is_numeric($fleetId)) return $this->_helper->json(array('error' => 'unknown fleet'));

            $fleet = $this->galaxyGateway->getFleet($fleetId);

            $coords = $fleet->getCoords();
            $destinationCoords = unserialize($destinationCoords);
            $speed  = $fleet->getTravelSpeed();
            $path   = $this->galaxyGateway->getPath($coords, $destinationCoords, $speed);

            return $this->_helper->json($path);
        }
        catch (Exception $e)
        {
            return $this->_helper->json(array('error' => $e->getMessage()));
        }
    }

    /**
     *
     */
    public function addfleetorderAction()
    {
        try {
            $fleetId   = $this->_params->fleetId;
            $order     = $this->_params->order;
            $destination = $this->_params->coords;

            $fleet = $this->galaxyGateway->getFleet($fleetId);

            if ( $fleet->nUser == $this->user->nId ) {
                $this->galaxyGateway->addOrder($order, $fleet, $destination);
                $this->_flashMessenger->setNamespace('success')->addMessage('order_confirmed');
            } else {
                $this->_flashMessenger->setNamespace('error')->addMessage('order_denied');
            }

        } catch (Exception $e) {
            $this->_flashMessenger->setNamespace('error')->addMessage('order_denied');

            $this->_flashMessenger->setNamespace('error')->addMessage($e->getMessage());
        }

        $this->_redirect('galaxy/system/show/');
    }
}
