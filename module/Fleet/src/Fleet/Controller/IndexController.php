<?php
namespace Fleet\Controller;

use Zend\View\Model\ViewModel;

 /**
  * @method integer getActive(String $itemType)
  * @method integer getSelected(String $itemType)
  * @method array selectedIds()
  */
class IndexController extends \Nouron\Controller\IngameController
{
    public function indexAction()
    {
        $sm = $this->getServiceLocator();

        $userId = $this->getActive('user');

        #$tick     = $sm->get('Nouron\Service\Tick');

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

        $fleetService = $sm->get('Fleet\Service\FleetService');

        if ($x && $y) {
            $entity = array('x' => $x, 'y' => $y);
            $fleets = $fleetService->getByCoordinates('fleets', array($x,$y));
        } elseif ( $colonyId ) {
            $entity = $fleetService->getColony($colonyId);
            $fleets = $fleetService->getFleetsByEntityId('colony', $colonyId);
        } elseif ( $objectId ) {
            $entity = $fleetService->getSystemObject($objectId);
            $fleets = $fleetService->getFleetsByEntityId('object', $objectId);
        } elseif ( $systemId) {
            $entity = $fleetService->getSystem($systemId);
            $fleets = $fleetService->getFleetsByEntityId('system', $systemId);
        } else {
            $fleets = $fleetService->getFleetsByUserId($userId);
            //$entity = $gw->getSystemObject($colonyId);
        }

        $x = isset($entity) ? $entity->x : $x;
        $y = isset($entity) ? $entity->y : $y;

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

    public function createAction()
    {
        if ($this->getRequest()->isPost()) {
            $sm = $this->getServiceLocator();
            $galaxyService = $sm->get('Galaxy\Service\Gateway');
            $fleetService = $sm->get('Fleet\Service\FleetService');

            # get active colony id
            $colony = $galaxyService->getColony($this->getActive('colony'));

            \Zend\Debug\Debug::dump($colony);

            # Flotten nur auf eigenen Kolonien erstellen!
            if ($this->getRequest()->isPost()) {
                $form = new \Galaxy\Form\Fleet();
                $form->setData($this->getRequest()->getPost());
                if ($form->isValid()) {
                    $newEntity = $form->getData();
                    unset($newEntity['id']);
                    unset($newEntity['submit']);
                    $newEntity['x'] = $colony->getX();
                    $newEntity['y'] = $colony->getY();
                    $newEntity['spot'] = $colony->getSpot();
                    $newEntity['user_id'] = $colony->getUserId();
                    $fid = $fleetService->saveFleet($newEntity);
                    #$success = true;
                    \Zend\Debug\Debug::dump($fid);
                } else {
                    \Zend\Debug\Debug::dump($form);
                }
            }
        }

        $this->redirect()->toUrl('/fleets/');
    }

    public function configAction()
    {
        $sm = $this->getServiceLocator();
        $fleetService = $sm->get('Fleet\Service\FleetService');
        $galaxyService = $sm->get('Galaxy\Service\Gateway');

        $resourcesService = $sm->get('Resources\Service\ResourcesService');
        $shipService      = $sm->get('Techtree\Service\ShipService');
        $researchService  = $sm->get('Techtree\Service\ResearchService');
        $personellService = $sm->get('Techtree\Service\PersonellService');

        $resources  = $resourcesService->getResources()->getArrayCopy('id');
        $ships      = $shipService->getEntities()->getArrayCopy('id');
        $researches = $researchService->getEntities()->getArrayCopy('id');
        $personells = $personellService->getEntities()->getArrayCopy('id');

        /// set view variable (visible foreign fleets too)
        $fid   = !empty($fid) ? $fid : $this->params()->fromRoute('id');
        $fleet = !empty($fid) ? $fleetService->getFleet($fid) : null;

        $fleetIsInColonyOrbit = false;

        if ($fleet) {
            $colony = $galaxyService->getColonyByCoords(array($fleet->getX(), $fleet->getY(), $fleet->getSpot()));
        } else {
            $colony = null;
        }

        if ($fleet && $fleet->getUserId() == $this->getActive('user')) {
            // own fleet
            if ($colony) {
                $fleetIsInColonyOrbit = true;
            }
            #$commands = $fleet->getOrders();
        }

        return new ViewModel(
            array(
                'fleet' => $fleet,
                'colony' => $colony,
                'fleetIsInColonyOrbit' => $fleetIsInColonyOrbit,
                'ships' => $ships,
                'personells' => $personells,
                'researches' => $researches,
                'resources' => $resources
            )
        );
    }
}
