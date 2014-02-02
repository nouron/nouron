<?php
namespace Fleet\Controller;

use Zend\View\Model\JsonModel;
use Zend\View\Model\ViewModel;

class JsonController extends \Nouron\Controller\IngameController
{
    /**
     * @return \Zend\View\Model\JsonModel
     */
    public function addtofleetAction()
    {
        $fleetId = (int) $this->params()->fromPost('id');
        if (empty($fleetId)) {
            $fleetId = $_SESSION['fleetId'];
        }

        $itemType = $this->params()->fromPost('itemType');
        $itemId   = (int) $this->params()->fromPost('itemId');
        $amount   = (int) $this->params()->fromPost('amount');
        $isCargo  = (bool) $this->params()->fromPost('isCargo');

        //get Colony Id
        $sm = $this->getServiceLocator();
        $gw = $sm->get('Galaxy\Service\Gateway');
        $fleetService = $sm->get('Fleet\Service\FleetService');
        $colony = $gw->getCurrentColony();
        $colonyId = (int) $colony->id;

        if (strtolower($itemType) == 'ship') {
            $transferred = $fleetService->transferShip($colony, $fleetId, $itemId, $amount, $isCargo);
        } elseif (strtolower($itemType) == 'research') {
            $transferred = $fleetService->transferResearch($colony, $fleetId, $itemId, $amount, $isCargo);
        } elseif (strtolower($itemType) == 'personell') {
            $transferred = $fleetService->transferPersonell($colony, $fleetId, $itemId, $amount, $isCargo);
        } elseif (strtolower($itemType) == 'resource') {
            $transferred = $fleetService->transferResource($colony, $fleetId, $itemId, $amount);
        } else {
            $transferred = 0;
        }

        $data = array(
            'colonyId' => $colonyId,
            'fleetId' => $fleetId,
            'itemType' => $itemType,
            'itemId' => $itemId,
            'isCargo' => $isCargo,
            'transferred' => $transferred
        );

        return new JsonModel( $data);
    }

    /**
     * @return \Zend\View\Model\JsonModel
     */
    public function getFleetTechnologiesAction()
    {
        $services = $this->getServiceLocator();

        $router = $services->get('router');
        $request = $services->get('request');

        $routeMatch = $router->match($request);

        $sm = $this->getServiceLocator();
        $gw = $sm->get('Fleet\Service\FleetService');
        $fleetId = $this->params()->fromRoute('id');
        if (empty($fleetId)) {
            $fleetId = $this->getActive('fleet');
        }
        if (empty($fleetId)) {
            throw new Exception('no fleet id!');
        }
        $fleetTechs = $gw->getFleetTechnologies($fleetId);
        return new JsonModel($fleetTechs);
    }

    /**
     * @return \Zend\View\Model\JsonModel
     */
    public function getFleetShipsAction()
    {
        return $this->getFleetEntitiesAction('ship');
    }

    /**
     * @return \Zend\View\Model\JsonModel
     */
    public function getFleetResourcesAction()
    {
        $fleetId = (int) $this->params()->fromRoute('id');
        if (empty($fleetId)) {
            $fleetId = $_SESSION['fleetId'];
        }
        $sm = $this->getServiceLocator();
        $gw = $sm->get('Fleet\Service\FleetService');
        $resGw = $sm->get('Resources\Service\ResourcesService');
        $resources = $resGw->getResources()->getArrayCopy('id');
        $fleetRes = $gw->getFleetResources("fleet_id = $fleetId");
        $fleetResArray = $fleetRes->getArrayCopy('resource_id');

        foreach ($fleetResArray as $resId => $tmp) {
            $tmp['name'] = $sm->get('translator')->translate($resources[$resId]['name']);
            $fleetResArray[$resId] = $tmp;
        }

        return new JsonModel( $fleetResArray);
    }
}

