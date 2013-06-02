<?php
namespace Galaxy\Controller;

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
        $isCargo  = (int) $this->params()->fromPost('isCargo');

        //get Colony Id
        $sm = $this->getServiceLocator();
        $gw = $sm->get('Galaxy\Service\Gateway');
        $colony = $gw->getCurrentColony();
        $colonyId = (int) $colony['id'];

        if (strtolower($itemType) == 'tech') {
            $transferred = $gw->transferTechnology($colonyId, $fleetId, $itemId, $amount, $isCargo);
        } elseif (strtolower($itemType) == 'resource') {
            $transferred = $gw->transferResource($colonyId, $fleetId, $itemId, $amount);
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
    public function getFleetTechnologiesAsJsonAction()
    {
        $fleetId = (int) $this->params()->fromRoute('id');
        if (empty($fleetId)) {
            $fleetId = $_SESSION['fleetId'];
        }
        $sm = $this->getServiceLocator();
        $gw = $sm->get('Galaxy\Service\Gateway');
        $techtreeGw = $sm->get('Techtree\Service\Gateway');
        $techs = $techtreeGw->getTechnologies()->getArrayCopy('id');
        $fleetTechs = $gw->getFleetTechnologies("fleet_id = $fleetId");
        $fleetTechsArray = $fleetTechs->getArrayCopy('tech_id');

        foreach ($fleetTechsArray as $techId => $tmp) {
            $tmp['type'] = $techs[$techId]['type'];
            $tmp['name'] = $sm->get('translator')->translate($techs[$techId]['name']);
            $fleetTechsArray[$techId] = $tmp;
        }

        return new JsonModel( $fleetTechsArray);
    }

    /**
     * @return \Zend\View\Model\JsonModel
     */
    public function getFleetResourcesAsJsonAction()
    {
        $fleetId = (int) $this->params()->fromRoute('id');
        if (empty($fleetId)) {
            $fleetId = $_SESSION['fleetId'];
        }
        $sm = $this->getServiceLocator();
        $gw = $sm->get('Galaxy\Service\Gateway');
        $resGw = $sm->get('Resources\Service\Gateway');
        $resources = $resGw->getResources()->getArrayCopy('id');
        $fleetRes = $gw->getFleetResources("fleet_id = $fleetId");
        $fleetResArray = $fleetRes->getArrayCopy('resource_id');

        foreach ($fleetResArray as $resId => $tmp) {
            #$tmp['type'] = $techs[$resId]['type'];
            $tmp['name'] = $sm->get('translator')->translate($resources[$resId]['name']);
            $fleetResArray[$resId] = $tmp;
        }

        return new JsonModel( $fleetResArray);
    }
}

