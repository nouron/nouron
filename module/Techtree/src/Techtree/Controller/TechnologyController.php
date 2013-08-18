<?php
namespace Techtree\Controller;

use Zend\View\Model\ViewModel;
use Zend\View\Model\JsonModel;
use Techtree\Service\Gateway;

class TechnologyController extends \Nouron\Controller\IngameController
{

    // add ap for leveldown
    // add ap for repair
    // add ap for levelup
    // levelup
    // leveldown
    public function orderAction()
    {
        try {
            $colonyId = $this->getActive('colony');
            $techId = $this->params()->fromRoute('id');
            $order  = $this->params()->fromRoute('order');
            $ap     = $this->params()->fromRoute('ap');
            $available_orders = array('add', 'remove', 'repair',
                                      'levelup', 'leveldown');
            if (!in_array($order, $available_orders)) {
                $this->getServiceLocator()
                     ->get('logger')
                     ->log(\Zend\Log\Logger::ERR, 'Invalid order type.');
                throw new \Techtree\Service\Exception('Invalid order type.');
            }

            $sm = $this->getServiceLocator();
            $techtreeGw = $sm->get('Techtree\Service\Gateway');

            $result = $techtreeGw->order($colonyId, $techId, $order, $ap);
            $message = array('success', $order . ' successfull');
            // TODO : OK-Nachricht
        } catch (\Techtree\Service\Exception $e) {
            // TODO : Error-Nachricht
            $this->getServiceLocator()
                 ->get('logger')
                 ->log(\Zend\Log\Logger::ERR, $e->getMessage());
            $result = false;
            $error = $e->getMessage();
            $message = array('error', $error);
        }

        return $this->forward()->dispatch(
            'Techtree\Controller\Technology',
            array('action' => 'tech', 'id'=>$techId, 'message'=>$message)
        );
    }

    /**
     *
     * @return \Zend\View\Model\JsonModel
     */
    public function repositionAction()
    {
        /**
         * TODO: allow this only to admins!
         */
        $techId = $this->params('id');
        $row = $this->params('row');
        $column = $this->params('column');
        $sm = $this->getServiceLocator();
        $techtreeGw = $sm->get('Techtree\Service\Gateway');
        $result = false;
        if (!empty($techId)) {
            $result = $techtreeGw->setGridPosition($techId, $row, $column);
        }

        return new JsonModel(array(
            'result' => $result,
        ));
    }

    /**
     *
     */
    public function techAction()
    {
        $techId = $this->params()->fromRoute('id');
        $message = $this->params('message');

        $sm = $this->getServiceLocator();
        $resourcesGw = $sm->get('Resources\Service\Gateway');
        $techtreeGw = $sm->get('Techtree\Service\Gateway');

        $tech = $techtreeGw->getTechnology($techId);

        $colonyId = $this->getActive('colony');
        $requiredTechsCheck = $techtreeGw->checkRequiredTechsByTechId($techId, $colonyId);
        $requiredResourcesCheck = $techtreeGw->checkRequiredResourcesByTechId($techId, $colonyId);
        $sm->get('logger')->log(\Zend\Log\Logger::INFO, array($requiredTechsCheck,$requiredResourcesCheck));
        $possessions = $techtreeGw->getPossessionsByColonyId($colonyId)->getArrayCopy('tech_id');
        $techs = $techtreeGw->getTechnologies()->getArrayCopy('id');

        if (array_key_exists($techId, $possessions)) {
            $level    = $possessions[$techId]['level'];
            $status_points   = $possessions[$techId]['status_points'];
            $ap_spend = $possessions[$techId]['ap_spend'];
        } else {
            $level = 0;
            $status_points = null;
            $ap_spend = 0;
        }

        $tech = $techtreeGw->getTechnology($techId);

        $result = new ViewModel(
            array(
                'tick' => (string) $sm->get('Nouron\Service\Tick'),
                'tech' => $tech,
                'required_techs_check' => $requiredTechsCheck,
                'required_resources_check' => $requiredResourcesCheck,
                'requirements' => $techtreeGw->getRequirementsByTechnologyId($techId)->getArrayCopy(),
                'costs' => $techtreeGw->getCostsByTechnologyId($techId)->getArrayCopy(),
                #'resource-possessions' => $resourceGw->getPossessions($colonyId),
                'possessions' => $possessions,
                'techs' => $techs,
                'resources' => $resourcesGw->getResources()->getArrayCopy('id'),
                'ap_spend' => $ap_spend,
                'ap_available' => $techtreeGw->getAvailableActionPoints($tech->type, $colonyId),
                'status_points' => $status_points,
                'level' => $level,
                'message' => $message,
            )
        );

        $result->setTerminal(true);
        return $result;
    }
}

