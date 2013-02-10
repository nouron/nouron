<?php
namespace Galaxy\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
//use Galaxy\Service\Gateway;

class FleetController extends AbstractActionController
{

    public function indexAction()
    {
        $sm = $this->getServiceLocator();

        $userId = 3;

        $tick     = $sm->get('Nouron\Service\Tick');

        # params from route
        $selectedIds = $this->selectedIds();
        $systemId = $selectedIds['systemId'];
        $objectId = $selectedIds['objectId'];
        $colonyId = $selectedIds['colonyId'];

        $cid = $this->params()->fromRoute('cid');
        $pid = $this->params()->fromRoute('pid');
        $sid = $this->params()->fromRoute('sid');
        $y = $this->params()->fromRoute('y');
        $x = $this->params()->fromRoute('x');
        $y = $this->params()->fromRoute('y');

        $gw = $sm->get('Galaxy\Service\Gateway');

        if ($x && $y) {
            $fleets = $gw->getByCoordinates('fleets', array($x,$y));
        } elseif ( $cid ) {
            print($cid);
            $fleets = $gw->getFleetsByEntityId('colony', $colonyId);
        } elseif ( $pid ) {
            $fleets = $gw->getFleetsByEntityId('object', $objectId);
        } elseif ( $sid) {
            $fleets = $gw->getFleetsByEntityId('system', $systemId);
        } else {
            $fleets = $gw->getFleetsByUserId($userId);
        }

        return new ViewModel(
            array(
                'fleets' => $fleets,
                'userId' => $userId,
                'x' => $x,
                'y' => $y,
                'sid' => $systemId,
                'pid' => $objectId,
                'cid' => $colonyId,
            )
        );
    }

    public function fleetAction()
    {
        $form = new \Galaxy\Form\Fleet();
        $sm = $this->getServiceLocator();
        $gw = $sm->get('Galaxy\Service\Gateway');

        if ($this->getRequest()->isPost()) {
            $form->setData($this->getRequest()->getPost());
            if ($form->isValid()) {
                $newEntity = $form->getData();
                $gw->saveFleet($newEntity);
                $form = new \Galaxy\Form\Fleet();
                return new ViewModel(
                    array(
                        'form' => $form,
                        'success' => true
                    )
                );
            } else {
                return new ViewModel(
                    array(
                        'form' => $form
                    )
                );
            }
        } else {
            return new ViewModel(
                array(
                    'form' => $form
                )
            );
        }
    }

    public function deleteFleetAction() {

    }
}
