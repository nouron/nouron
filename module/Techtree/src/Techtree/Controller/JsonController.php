<?php
namespace Techtree\Controller;

use Zend\View\Model\JsonModel;

/**
 *
 * @author tt
 *
 */
class JsonController extends \Nouron\Controller\IngameController
{
    // /**
    //  *
    //  * @return \Zend\View\Model\JsonModel
    //  */
    // public function getRequirementsAction()
    // {
    //     $sm = $this->getServiceLocator();
    //     $gw = $sm->get('Techtree\Service\BuildingService');
    //     return new JsonModel( $gw->getRequirementsAsArray() );
    // }

    // /**
    //  *
    //  * @return \Zend\View\Model\JsonModel
    //  */
    // public function getRequirementsForTechnologyAction()
    // {
    //     $techId = $this->params()->fromRoute('id');
    //     $sm = $this->getServiceLocator();
    //     $gw = $sm->get('Techtree\Service\BuildingService');
    //     return new JsonModel( $gw->getRequirementsByTechnologyId($techId) );
    // }

    // /**
    //  *
    //  * @return \Zend\View\Model\ViewModel
    //  */
    // public function getCostsForTechnologyAction()
    // {
    //     $techId = $this->params()->fromRoute('id');
    //     $sm = $this->getServiceLocator();
    //     $techtreeGw = $sm->get('Techtree\Service\BuildingService');
    //     $resourcesGw = $sm->get('Resources\Service\ResourcesService');
    //     //return new JsonModel( $gw->getCostsByTechnologyId($techId) );
    //     $result = new ViewModel(
    //         array(
    //             'costs' => $techtreeGw->getCostsByTechnologyId($techId),
    //             'resources' => $resourcesGw->getResources(),
    //         )
    //     );

    //     $result->setTerminal(true);
    //     return $result;
    // }

    /**
     *
     */
    public function getColonyTechnologiesAction()
    {
        $sm = $this->getServiceLocator();
        $gw = $sm->get('Techtree\Service\ColonyService');
        $colonyId = $this->params()->fromRoute('id');
        if (empty($colonyId)) {
            $colonyId = $this->getActive('colony');
        }
        $gw->setColonyId($colonyId);
        $coloTechtree = $gw->getTechtree();
        return new JsonModel($coloTechtree);
    }

    // /**
    //  *
    //  */
    // public function getTechnologiesAction()
    // {
    //     $sm = $this->getServiceLocator();
    //     $gw = $sm->get('Techtree\Service\BuildingService');
    //     $techs = $gw->getTechnologies()->getArrayCopy('id');

    //     foreach ($techs as $id => $tech) {
    //         $techs[$id]['name'] = $sm->get('translator')->translate( $tech['name'] );
    //     }

    //     return new JsonModel($techs);
    // }

    // /**
    //  * @todo
    //  * @return JSON
    //  */
    // public function addToFleetAction()
    // {
    //     $fleetId = (int) $this->params()->fromRoute('id');
    //     $techId = (int) $this->params()->fromQuery('tech');
    //     $resId  = (int) $this->params()->fromQuery('res');
    //     $amount = (int) $this->params()->fromQuery('amount');
    //     $isCargo = (int) $this->params()->fromQuery('isCargo');

    //     //get Colony Id
    //     //$colonyId = 0
    //     $colonyId = $this->getActive('colony');
    //     $sm = $this->getServiceLocator();
    //     $gw = $sm->get('Techtree\Service\BuildingService');
    //     $transferred = $gw->transferTechnology($colonyId, $fleetId, $techId, $amount, $isCargo);

    //     $data = array(
    //         'colonyId' => $colonyId,
    //         'fleetId' => $fleetId,
    //         'techId' => $techId,
    //         'isCargo' => $isCargo,
    //         'transferred' => $transferred
    //     );

    //     return new JsonModel($data);
    // }
}

