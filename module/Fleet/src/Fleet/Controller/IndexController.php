<?php
namespace Fleet\Controller;

use Zend\View\Model\ViewModel;

 /**
  * @method integer getActive(String $itemType)
  * @method integer getSelected(String $itemType)
  * @method array selectedIds()
  */
class IndexController extends \Core\Controller\IngameController
{
    public function indexAction()
    {
        $sm = $this->getServiceLocator();

        $userId = $this->getActive('user');

        #$tick     = $sm->get('Core\Service\Tick');

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
            $ownFleets = $fleetService->getByCoordinates('fleets', array($x,$y));
            $foreignFleets = $fleetService->getByCoordinates('fleets', array($x,$y));
        } elseif ( $colonyId ) {
            $entity = $fleetService->getColony($colonyId);
            $ownFleets = $fleetService->getFleetsByEntityId('colony', $colonyId);
            $foreignFleets = $fleetService->getFleetsByEntityId('colony', $colonyId);
        } elseif ( $objectId ) {
            $entity = $fleetService->getSystemObject($objectId);
            $ownFleets = $fleetService->getFleetsByEntityId('object', $objectId);
            $foreignFleets = $fleetService->getFleetsByEntityId('object', $objectId);
        } elseif ( $systemId) {
            $entity = $fleetService->getSystem($systemId);
            $ownFleets = $fleetService->getFleetsByEntityId('system', $systemId);
            $foreignFleets = $fleetService->getFleetsByEntityId('system', $systemId);
        } else {
            $ownFleets = $fleetService->getFleetsByUserId($userId);
            $foreignFleets = $fleetService->getFleetsByUserId($userId);
            //$entity = $gw->getSystemObject($colonyId);
        }

        $x = isset($entity) ? $entity->getX() : $x;
        $y = isset($entity) ? $entity->getY() : $y;

        return new ViewModel(
            array(
                'ownFleets' => $ownFleets,
                'foreignFleets' => $foreignFleets,
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
        $colonyService = $sm->get('Colony\Service\ColonyService');

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
            $colony = $colonyService->getColonyByCoords(array($fleet->getX(), $fleet->getY(), $fleet->getSpot()));
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
