<?php
namespace Techtree\Controller;

use Zend\View\Model\JsonModel;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Techtree\Service\Gateway;

class JsonController extends AbstractActionController
{
    public function getRequirementsAction()
    {
        $sm = $this->getServiceLocator();
        $gw = $sm->get('Techtree\Service\Gateway');
        return new JsonModel( $gw->getRequirementsAsArray() );
    }

    public function getRequirementsForTechnologyAction()
    {
        $techId = $this->params()->fromRoute('id');
        $sm = $this->getServiceLocator();
        $gw = $sm->get('Techtree\Service\Gateway');
        return new JsonModel( $gw->getRequirementsByTechnologyId($techId) );
    }

    public function getCostsForTechnologyAction()
    {
        $techId = $this->params()->fromRoute('id');
        $sm = $this->getServiceLocator();
        $techtreeGw = $sm->get('Techtree\Service\Gateway');;
        $resourcesGw = $sm->get('Resources\Service\Gateway');
        //return new JsonModel( $gw->getCostsByTechnologyId($techId) );
        $result = new ViewModel(
            array(
                'costs' => $techtreeGw->getCostsByTechnologyId($techId),
                'resources' => $resourcesGw->getResources(),
            )
        );

        $result->setTerminal(true);
        return $result;
    }
}

