<?php
namespace Techtree\Controller;

use Zend\View\Model\ViewModel;
use Zend\View\Model\JsonModel;
use Techtree\Service\Gateway;

class TechnologyController extends \Nouron\Controller\IngameController
{
    public function orderAction()
    {
        $colonyId = 0; // TODO: take from Session
        $techId = $this->params()->fromRoute('id');
        $order  = $this->params()->fromRoute('order');

        if (!in_array($order,array('add','remove','repair'))) {
            $this->getServiceLocator()->get('logger')->log(\Zend\Log\Logger::ERR, 'Invalid order type.');
            $this->redirect()->toRoute('techtree');
        }

        $sm = $this->getServiceLocator();
        $techtreeGw = $sm->get('Techtree\Service\Gateway');

        try {
            $result = $techtreeGw->order($colonyId, $techId, $order);

            // TODO : OK-Nachricht

        } catch (Exception $e) {
            // TODO : Error-Nachricht
        }

        $this->redirect()->toRoute('techtree');
    }

    /**
     *
     * @return \Zend\View\Model\JsonModel
     */
    public function updatePositionAction()
    {
        $techId = $this->params('id');
        $row = $this->params('row');
        $column = $this->params('column');
        $sm = $this->getServiceLocator();
        $techtreeGw = $sm->get('Techtree\Service\Gateway');
        $result = false;
        if (!empty($techId)) {
            $result = $techtreeGw->setGridPosition($techId, $row, $column);
        }
        $this->redirect()->toRoute('techtree');
        return new JsonModel(array(
            'result' => $result,
        ));
    }
}

