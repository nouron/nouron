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
        $fleetId = (int) $this->params()->fromRoute('id');
        if (empty($fleetId)) {
            $fleetId = 10; // TODO: get from session
        }
        $techId = (int) $this->params()->fromQuery('tech');
        $resId  = (int) $this->params()->fromQuery('res');
        $amount = (int) $this->params()->fromQuery('amount');
        $isCargo = (int) $this->params()->fromQuery('isCargo');

        //get Colony Id
        $sm = $this->getServiceLocator();
        $gw = $sm->get('Galaxy\Service\Gateway');
        $colony = $gw->getCurrentColony();
        $colonyId = $colony['id'];
        #$fleetId = 17;

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
        $fleetTechs = $gw->getFleetTechnologies("fleet_id = $fleetId");
        $fleetTechsArray = $fleetTechs->toArray('tech_id');

//         foreach ($fleetTechsArray as $i => $tmp) {
//             $tmp['technology'] = $this->translate($tmp['technology']);
//             $fleetTechsArray[$i] = $tmp;
//         }

        return new JsonModel( $fleetTechsArray);
    }
}

