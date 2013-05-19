<?php
namespace Techtree\Controller;

use Zend\View\Model\ViewModel;
use Zend\View\Model\JsonModel;
use Techtree\Service\Gateway;

class TechnologyController extends \Nouron\Controller\IngameController
{
    public function orderAction()
    {
        try {
            $colonyId = 0; // TODO: take from Session
            $techId = $this->params()->fromRoute('id');
            $order  = $this->params()->fromRoute('order');

            if (!in_array($order,array('add','remove','repair','cancel'))) {
                //$this->getServiceLocator()->get('logger')->log(\Zend\Log\Logger::ERR, 'Invalid order type.');
                throw Exception('Invalid order type.');
            }

            $sm = $this->getServiceLocator();
            $techtreeGw = $sm->get('Techtree\Service\Gateway');

            $ap = 1;
            $result = $techtreeGw->order($colonyId, $techId, $order, $ap);
            $error = null;
            // TODO : OK-Nachricht
        } catch (Exception $e) {
            // TODO : Error-Nachricht
            $result = false;
            $error = $e->getMessage();
        }
        return new JsonModel(array(
            'result' => $result,
            'error'  => $error
        ));
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

        $sm = $this->getServiceLocator();
        $resourcesGw = $sm->get('Resources\Service\Gateway');
        $techtreeGw = $sm->get('Techtree\Service\Gateway');

        $addActionPointsUrl    = $this->url()->fromRoute('techtree/order', array('id' => $techId, 'order'=>'add'));
        $removeActionPointsUrl = $this->url()->fromRoute('techtree/order', array('id' => $techId, 'order'=>'remove'));
        //$levelRepairUrl = $this->url()->fromRoute('techtree/order', array('id' => $techId, 'order'=>'repair'));
        $cancelActionPointsUrl = $this->url()->fromRoute('techtree/order', array('id' => $techId, 'order'=>'cancel'));

        $tech = $techtreeGw->getTechnology($techId);
//         $logger = $sm->get('logger');
//         $logger->log(\Zend\Log\Logger::INFO, serialize($tech));

        $colonyId = 0;
        $requiredTechsCheck = $techtreeGw->checkRequiredTechsByTechId($techId, $colonyId);
        $requiredResourcesCheck = $techtreeGw->checkRequiredResourcesByTechId($techId, $colonyId);
        $sm->get('logger')->log(\Zend\Log\Logger::INFO, array($requiredTechsCheck,$requiredResourcesCheck));
        $possessions = $techtreeGw->getPossessionsByColonyId($colonyId)->toArray('tech_id');
        $techs = $techtreeGw->getTechnologies()->toArray('id');

        if (array_key_exists($techId, $possessions)) {
            $level    = $possessions[$techId]['level'];
            $ap_spend = $possessions[$techId]['ap_spend'];
        } else {
            $level = 0;
            $ap_spend = 0;
        }

        $order = $techtreeGw->getOrderByTechnologyId($techId, $colonyId);
        $ap_ordered = $order ? $order->ap_ordered : 0;

        $urls = array(
            'add' => null,
            'remove' => null,
            'cancel' => null
        );
        if ((!$order || $order->order == 'add') && $requiredTechsCheck && $requiredResourcesCheck) {
            $sm->get('logger')->log(\Zend\Log\Logger::INFO, 'a');
            $urls['add'] = $addActionPointsUrl;
        }
        if ((!$order || $order->order == 'remove') && $level > 0) {
            $sm->get('logger')->log(\Zend\Log\Logger::INFO, 'b');
            $urls['remove'] = $removeActionPointsUrl;
        }

        if ($order && $ap_spend > 0) {
            $sm->get('logger')->log(\Zend\Log\Logger::INFO, 'c');
            $urls['cancel'] = $cancelActionPointsUrl;
        }
        $tech = $techtreeGw->getTechnology($techId);
        $percentage_completed = round(($ap_spend / $techs[$techId]['ap_for_levelup']) * 100);
        $percentage_gain      = round(($ap_ordered / $techs[$techId]['ap_for_levelup']) * 100);

        $result = new ViewModel(
            array(
                'tick' => (string) $sm->get('Nouron\Service\Tick'),
                'tech' => $tech,
                'requirements' => $techtreeGw->getRequirementsByTechnologyId($techId),
                'costs' => $techtreeGw->getCostsByTechnologyId($techId),
                #'resource-possessions' => $resourceGw->getPossessions($colonyId),
                'order' => $order,
                'possessions' => $possessions,
                'techs' => $techs,
                'resources' => $resourcesGw->getResources()->toArray('id'),
                'urls' => $urls,
                'percentage_completed' => $percentage_completed,
                'percentage_gain' => $percentage_gain,
                'ap_spend' => $ap_spend,
                'ap_ordered' => $ap_ordered
            )
        );

        $result->setTerminal(true);
        return $result;
    }
}

