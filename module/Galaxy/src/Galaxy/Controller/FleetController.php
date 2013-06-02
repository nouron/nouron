<?php
namespace Galaxy\Controller;

use Zend\View\Model\ViewModel;

class FleetController extends \Nouron\Controller\IngameController
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

//         $cid = $this->params()->fromRoute('cid');
//         $pid = $this->params()->fromRoute('pid');
//         $sid = $this->params()->fromRoute('sid');
        $y = $this->params()->fromRoute('y');
        $x = $this->params()->fromRoute('x');

        $gw = $sm->get('Galaxy\Service\Gateway');

        if ($x && $y) {
            $entity = array('x' => $x, 'y' => $y);
            $fleets = $gw->getByCoordinates('fleets', array($x,$y));
        } elseif ( $colonyId ) {
            $entity = $gw->getColony($colonyId);
            $fleets = $gw->getFleetsByEntityId('colony', $colonyId);
        } elseif ( $objectId ) {
            $entity = $gw->getSystemObject($objectId);
            $fleets = $gw->getFleetsByEntityId('object', $objectId);
        } elseif ( $systemId) {
            $entity = $gw->getSystem($systemId);
            $fleets = $gw->getFleetsByEntityId('system', $systemId);
        } else {
            $fleets = $gw->getFleetsByUserId($userId);
            //$entity = $gw->getSystemObject($colonyId);
        }

        $x = isset($entity) ? $entity['x'] : $x;
        $y = isset($entity) ? $entity['y'] : $y;

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
        $resourcesGw = $sm->get('Resources\Service\Gateway');
        $resources = $resourcesGw->getResources()->getArrayCopy('id');
        $techtreeGw = $sm->get('Techtree\Service\Gateway');
        $techs = $techtreeGw->getTechnologies()->getArrayCopy('id');
        if ($this->getRequest()->isPost()) {
            $form->setData($this->getRequest()->getPost());
            if ($form->isValid()) {
                $newEntity = $form->getData();
                unset($newEntity['id']);
                unset($newEntity['submit']);
                $fid = $gw->saveFleet($newEntity);
                $form = new \Galaxy\Form\Fleet();
                $success = true;
                \Zend\Debug\Debug::dump($fid);
            } else {
                \Zend\Debug\Debug::dump($form);
            }
        }

        /// set view variable (visible foreign fleets too)
        $fid = !empty($fid) ? $fid : $this->params()->fromRoute('fid');
        $fleet = !empty($fid) ? $gw->getFleet($fid) : null;

        $fleetIsInColonyOrbit = false;

        $userId = $_SESSION['userId'];

        if ($fleet && $fleet->user_id == $userId) {
            // own fleet
            $colony = $gw->getColonyByCoords(array($fleet['x'],$fleet['y'],$fleet['spot']));
            if ($colony) {
                $fleetIsInColonyOrbit = true;
                //get Colony Id
                $colonyId = $colony['id'];
            }
            #$commands = $fleet->getOrders();
        }

        return new ViewModel(
            array(
                'form' => $form,
                'fleet' => $fleet,
                'colony' => $colony,
                'techs' => $techs,
                'fleetIsInColonyOrbit' => $fleetIsInColonyOrbit,
//                 'ships' => $ships,
//                 'advisors' => $advisors,
//                 'techs' => $buildingsAndResearches,
                 'resources' => $resources,
//                 'fleetShips' => $fleetShips,
//                 'fleetCrew' => $fleetCrew,
//                 'fleetCargoShips' => $fleetCargoShips,
//                 'fleetPassengers' => $fleetPassengers,
//                 'fleetCargoTechs' => $fleetCargoTechs,
//                 'fleetCargoResources' => $fleetCargoResources,
            )
        );
    }

    public function orderAction()
    {
        $form = new \Galaxy\Form\Fleet();
        $sm = $this->getServiceLocator();
        $gw = $sm->get('Galaxy\Service\Gateway');

        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost();
            $order = (string) $data['order'];
            $fleetId = (int) $data['fleetId'];
            $coords = $data['coords'];
            $gw->addOrder($fleetId, $order, $coords, array());
        }


        $selectedIds = $this->selectedIds(); /* ugly: find a better solution */
        $systemId = $selectedIds['systemId'];
        $this->redirect()->toUrl('/galaxy/'.$systemId);
    }
}
