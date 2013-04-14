<?php
namespace Techtree\Controller;

use Zend\View\Model\ViewModel;
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
}

