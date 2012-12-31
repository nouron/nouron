<?php
namespace Techtree\Controller;

use Zend\View\Model\JsonModel;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Techtree\Service\Gateway;

class JsonController extends AbstractActionController
{
    public function getModalHtmlForTechnologyAction()
    {
        $techId = $this->params()->fromRoute('id');

        $sm = $this->getServiceLocator();
        $resourcesGw = $sm->get('Resources\Service\Gateway');
        $techtreeGw = $sm->get('Techtree\Service\Gateway');

        $urls = array(
            $this->url()->fromRoute('techtree/technology', array('controller' => 'technology', 'action' => 'order', 'id' => $techId, 'order'=>'add')),
            $this->url()->fromRoute('techtree/technology', array('controller' => 'technology', 'action' => 'order', 'id' => $techId, 'order'=>'remove')),
            $this->url()->fromRoute('techtree/technology', array('controller' => 'technology', 'action' => 'order', 'id' => $techId, 'order'=>'repair')),
        );

        $result = new ViewModel(
            array(
                'tech' => $techtreeGw->getTechnology($techId),
                'requirements' => $techtreeGw->getRequirementsByTechnologyId($techId),
                'costs' => $techtreeGw->getCostsByTechnologyId($techId),
                #'resource-possessions' => $resourceGw->getPossessions($colonyId),
                #'tech-possessions' => $techtreeGw->getPossessions($colonyId),
                'techs' => $techtreeGw->getTechnologies()->toArray('id'),
                'resources' => $resourcesGw->getResources()->toArray('id'),
                'urls' => $urls
            )
        );

        $result->setTerminal(true);
        return $result;
    }

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
        $techtreeGw = $sm->get('Techtree\Service\Gateway');
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

