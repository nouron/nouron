<?php
namespace Galaxy\Controller;

use Zend\View\Model\JsonModel;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Techtree\Service\Gateway;

/**
 *
 * @author tt
 *
 */
class JsonController extends AbstractActionController
{
    /**
     * @return \Zend\View\Model\JsonModel
     */
    public function addtofleetAction()
    {
        $fleetId = (int) $this->params()->fromPost('id');
        if (empty($fleetId)) {
            $fleetId = 10; // TODO: get from session
        }

        $techId  = (int) $this->params()->fromPost('tech');
        $amount  = (int) $this->params()->fromPost('amount');
        $isCargo = (int) $this->params()->fromPost('isCargo');

        //get Colony Id
        $sm = $this->getServiceLocator();
        $gw = $sm->get('Galaxy\Service\Gateway');
        $colony = $gw->getCurrentColony();
        $colonyId = (int) $colony['id'];

        $transferred = $gw->transferTechnology($colonyId, $fleetId, $techId, $amount, $isCargo);

        $data = array(
            'colonyId' => $colonyId,
            'fleetId' => $fleetId,
            'techId' => $techId,
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
            $fleetId = 10; // TODO: get from session
        }
        $sm = $this->getServiceLocator();
        $gw = $sm->get('Galaxy\Service\Gateway');
        $techtreeGw = $sm->get('Techtree\Service\Gateway');
        $techs = $techtreeGw->getTechnologies()->toArray('id');
        $fleetTechs = $gw->getFleetTechnologies("fleet_id = $fleetId");
        $fleetTechsArray = $fleetTechs->toArray('tech_id');

        foreach ($fleetTechsArray as $techId => $tmp) {
            $tmp['type'] = $techs[$techId]['type'];
            $tmp['name'] = $sm->get('translator')->translate($techs[$techId]['name']);
            $fleetTechsArray[$techId] = $tmp;
        }

        return new JsonModel( $fleetTechsArray);
    }
}

