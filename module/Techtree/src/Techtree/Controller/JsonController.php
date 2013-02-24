<?php
namespace Techtree\Controller;

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
     *
     * @return \Zend\View\Model\ViewModel
     */
    public function getModalHtmlForTechnologyAction()
    {
        $techId = $this->params()->fromRoute('id');

        $sm = $this->getServiceLocator();
        $resourcesGw = $sm->get('Resources\Service\Gateway');
        $techtreeGw = $sm->get('Techtree\Service\Gateway');

        $levelAddUrl    = $this->url()->fromRoute('techtree/technology', array('controller' => 'technology', 'action' => 'order', 'id' => $techId, 'order'=>'add'));
        $levelRemoveUrl = $this->url()->fromRoute('techtree/technology', array('controller' => 'technology', 'action' => 'order', 'id' => $techId, 'order'=>'remove'));
        $levelRepairUrl = $this->url()->fromRoute('techtree/technology', array('controller' => 'technology', 'action' => 'order', 'id' => $techId, 'order'=>'repair'));


        $tech = $techtreeGw->getTechnology($techId);
//         $logger = $sm->get('logger');
//         $logger->log(\Zend\Log\Logger::INFO, serialize($tech));

        $colonyId = 0;
        $requiredTechsCheck = $techtreeGw->checkRequiredTechsByTechId($techId, $colonyId);
        $requiredResourcesCheck = $techtreeGw->checkRequiredResourcesByTechId($techId, $colonyId);

        $level = $techtreeGw->getLevelByTechnologyId($techId, $colonyId);

        $urls = array(
            'add' => null,
            'remove' => null,
            'repair' => null
        );
        if ($requiredTechsCheck && $requiredResourcesCheck) {
            $urls['add'] = $levelAddUrl;
        }
        if ($level > 0) {
            $urls['remove'] = $levelRemoveUrl;
        }
        if ($requiredResourcesCheck) {
            $urls['repair'] = $levelRepairUrl;
        }

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

    /**
     *
     * @return \Zend\View\Model\JsonModel
     */
    public function getRequirementsAction()
    {
        $sm = $this->getServiceLocator();
        $gw = $sm->get('Techtree\Service\Gateway');
        return new JsonModel( $gw->getRequirementsAsArray() );
    }

    /**
     *
     * @return \Zend\View\Model\JsonModel
     */
    public function getRequirementsForTechnologyAction()
    {
        $techId = $this->params()->fromRoute('id');
        $sm = $this->getServiceLocator();
        $gw = $sm->get('Techtree\Service\Gateway');
        return new JsonModel( $gw->getRequirementsByTechnologyId($techId) );
    }

    /**
     *
     * @return \Zend\View\Model\ViewModel
     */
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

    /**
     *
     */
    public function gettechtreeasjsonAction()
    {
        $sm = $this->getServiceLocator();
        $gw = $sm->get('Techtree\Service\Gateway');

        $colonyId = $this->params()->fromRoute('id');
        if (empty($colonyId)) {

            $galaxyGw = $sm->get('Galaxy\Service\Gateway');
            $colony = $galaxyGw->getCurrentColony();
            $colonyId = $colony['id'];

        }
        $coloTechtree = $gw->getTechtreeByColonyId($colonyId);
        return new JsonModel($coloTechtree);
    }

    /**
     *
     */
    public function gettechnologiesasjsonAction()
    {
        $sm = $this->getServiceLocator();
        $gw = $sm->get('Techtree\Service\Gateway');
        $techs = $gw->getTechnologies()->toArray('id');

        foreach ($techs as $id => $tech) {
            $techs[$id]['name'] = $sm->get('translator')->translate( $tech['name'] );
        }

        return new JsonModel($techs);
    }

    /**
     * @todo
     * @return JSON
     */
    public function addtofleetAction()
    {
        $fleetId = (int) $this->params()->fromRoute('id');
        $techId = (int) $this->params()->fromQuery('tech');
        $resId  = (int) $this->params()->fromQuery('res');
        $amount = (int) $this->params()->fromQuery('amount');
        $isCargo = (int) $this->params()->fromQuery('isCargo');

        //get Colony Id
        $colonyId = 0;

        $sm = $this->getServiceLocator();
        $gw = $sm->get('Techtree\Service\Gateway');
        $transferred = $gw->transferTechnology($colonyId, $fleetId, $techId, $amount, $isCargo);

        $data = array(
            'colonyId' => $colonyId,
            'fleetId' => $fleetId,
            'techId' => $techId,
            'isCargo' => $isCargo,
            'transferred' => $transferred
        );

        return new JsonModel($data);
    }
}

