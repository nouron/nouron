<?php
namespace Techtree\Controller;

use Zend\View\Model\ViewModel;

class TechnologyController extends \Nouron\Controller\IngameController
{

    // add ap for leveldown
    // add ap for repair
    // add ap for levelup
    // levelup
    // leveldown
    public function orderAction()
    {
        $colonyId = $this->getActive('colony');
        $type   = $this->params()->fromRoute('entitytype');
        $techId = $this->params()->fromRoute('id');
        $order  = $this->params()->fromRoute('order');
        $ap     = $this->params()->fromRoute('ap');
        $sm = $this->getServiceLocator();
        switch (strtolower($type)) {
            case 'building':
                $service = $sm->get('Techtree\Service\BuildingService');
                break;
            case 'research':
                $service = $sm->get('Techtree\Service\ResearchService');
                break;
            case 'ship':
                $service = $sm->get('Techtree\Service\ShipService');
                break;
            case 'personell':
                $service = $sm->get('Techtree\Service\PersonellService');
                break;
            default:
                throw new \Exception('unknown techtree service');
        }

        try {
            if (in_array($order, array('add', 'remove', 'repair'))) {
                $result = $service->invest($colonyId, $techId, $order, $ap);
                if ($result) {
                    $message = array('success', $order . ' successfull');
                } else {
                    $message = array('error', $order . ' failed');
                }
                // TODO : OK-Nachricht
            } else if ($order == 'levelup') {
                $result = $service->levelup($colonyId, $techId);
                if ($result) {
                    $message = array('success', 'levelup successfull');
                } else {
                    $message = array('error', 'levelup failed');
                }
                // TODO : OK-Nachricht
            } else if ($order == 'leveldown') {
                $result = $service->leveldown($colonyId, $techId);
                if ($result) {
                    $message = array('success', 'leveldown successfull');
                } else {
                    $message = array('error', 'leveldown failed');
                }
                // TODO : OK-Nachricht
            } else {
                $this->getServiceLocator()
                     ->get('logger')
                     ->log(\Zend\Log\Logger::ERR, 'Invalid order type.');
                throw new \Techtree\Service\Exception('Invalid order type.');
            }
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
            array('action' => $type, 'id'=>$techId, 'message'=>$message)
        );
    }

    /**
     *
     * @return \Zend\View\Model\ViewModel
     */
    public function buildingAction()
    {
        $sm = $this->getServiceLocator();
        $sm->get('logger')->log(\Zend\Log\Logger::ERR, 'testtesttes');

        $buildingId = $this->params()->fromRoute('id');
        $message = $this->params('message');

        $colonyId = $this->getActive('colony');
        $sm = $this->getServiceLocator();
        $resourcesService = $sm->get('Resources\Service\ResourcesService');
        $buildingService  = $sm->get('Techtree\Service\BuildingService');
        $personellService = $sm->get('Techtree\Service\PersonellService');
        $colonyService = $sm->get('Techtree\Service\ColonyService');
        $colonyService->setColonyId($colonyId);
        $techtree = $colonyService->getTechtree();
        $building = $techtree['building'][$buildingId];

        $requiredBuildingsCheck = $buildingService->checkRequiredBuildingsByEntityId($colonyId, $buildingId);
        $costs = $buildingService->getEntityCosts($buildingId);
        $requiredResourcesCheck = $resourcesService->check($costs, $colonyId);
        $sm->get('logger')->log(\Zend\Log\Logger::INFO, 'required buildings check : ' . $requiredBuildingsCheck);
        $sm->get('logger')->log(\Zend\Log\Logger::INFO, 'required resources check : ' . $requiredResourcesCheck);
        $possessions = $colonyService->getBuildings()->getArrayCopy('building_id');
        $buildings = $buildingService->getEntities()->getArrayCopy('id');

        if (array_key_exists($buildingId, $possessions)) {
            #$level    = $possessions[$buildingId]['level'];
            $status_points   = $possessions[$buildingId]['status_points'];
            #$ap_spend = $possessions[$buildingId]['ap_spend'];
        } else {
            #$level = 0;
            $status_points = null;
            #$ap_spend = 0;
        }

        $result = new ViewModel(
            array(
                'tick' => (string) $sm->get('Nouron\Service\Tick'),
                'building' => $building,
                'required_buildings_check' => $requiredBuildingsCheck,
                'required_resources_check' => $requiredResourcesCheck,
                'costs' => $buildingService->getEntityCosts($buildingId),
                'possessions' => $possessions,
                'buildings' => $buildings,
                'resources' => $resourcesService->getResources()->getArrayCopy('id'),
                'ap_available' => $personellService->getAvailableActionPoints('construction', $colonyId),
                'status_points' => $status_points,
                'message' => $message,
            )
        );

        $result->setTerminal($this->getRequest()->isXmlHttpRequest());
        return $result;
    }

    /**
     *
     * @return \Zend\View\Model\ViewModel
     */
    public function researchAction()
    {
        $researchId = $this->params()->fromRoute('id');
        $message = $this->params('message');

        $colonyId = $this->getActive('colony');
        $sm = $this->getServiceLocator();
        $resourcesService = $sm->get('Resources\Service\ResourcesService');
        $buildingService  = $sm->get('Techtree\Service\BuildingService');
        $researchService  = $sm->get('Techtree\Service\ResearchService');
        $personellService = $sm->get('Techtree\Service\PersonellService');
        $colonyService = $sm->get('Techtree\Service\ColonyService');
        $colonyService->setColonyId($colonyId);
        $techtree = $colonyService->getTechtree();
        $research = $techtree['research'][$researchId];

        $sm->get('logger')->log(\Zend\Log\Logger::INFO, array($colonyId, $researchId));

        $requiredBuildingsCheck = $researchService->checkRequiredBuildingsByEntityId($colonyId, $researchId);
        $costs = $researchService->getEntityCosts($researchId);
        $requiredResourcesCheck = $resourcesService->check($costs, $colonyId);
        $sm->get('logger')->log(\Zend\Log\Logger::INFO, 'required buildings check : ' . $requiredBuildingsCheck);
        $sm->get('logger')->log(\Zend\Log\Logger::INFO, 'required resources check : ' . $requiredResourcesCheck);
        #$colonyBuildings  = $colonyService->getBuildings()->getArrayCopy('building_id');
        $colonyResearches = $colonyService->getResearches()->getArrayCopy('research_id');
        $buildings  = $buildingService->getEntities()->getArrayCopy('id');
        $researches = $researchService->getEntities()->getArrayCopy('id');

        $result = new ViewModel(
            array(
                'tick' => (string) $sm->get('Nouron\Service\Tick'),
                'research' => $research,
                'required_buildings_check' => $requiredBuildingsCheck,
                'required_resources_check' => $requiredResourcesCheck,
                'buildings' => $buildings,
                'costs' => $researchService->getEntityCosts($researchId),
                'possessions' => $colonyResearches,
                'researches' => $researches,
                'resources' => $resourcesService->getResources()->getArrayCopy('id'),
                'ap_available' => $personellService->getAvailableActionPoints('research', $colonyId),
                'message' => $message,
            )
        );

        $result->setTerminal(true);
        return $result;
    }


    public function shipAction()
    {
        $shipId = $this->params()->fromRoute('id');
        $message = $this->params('message');

        $colonyId = $this->getActive('colony');
        $sm = $this->getServiceLocator();
        $resourcesService = $sm->get('Resources\Service\ResourcesService');
        $buildingService  = $sm->get('Techtree\Service\BuildingService');
        $researchesService= $sm->get('Techtree\Service\ResearchService');
        $personellService = $sm->get('Techtree\Service\PersonellService');
        $shipService   = $sm->get('Techtree\Service\ShipService');
        $colonyService = $sm->get('Techtree\Service\ColonyService');
        $colonyService->setColonyId($colonyId);
        $techtree = $colonyService->getTechtree();
        $ship = $techtree['ship'][$shipId];

        $requiredBuildingsCheck  = $shipService->checkRequiredBuildingsByEntityId($colonyId, $shipId);
        $requiredResearchesCheck = $shipService->checkRequiredResearchesByEntityId($colonyId, $shipId);
        $costs = $shipService->getEntityCosts($shipId);
        $requiredResourcesCheck = $resourcesService->check($costs, $colonyId);
        $sm->get('logger')->log(\Zend\Log\Logger::INFO, 'required buildings check : ' . $requiredBuildingsCheck);
        $sm->get('logger')->log(\Zend\Log\Logger::INFO, 'required researches check : ' . $requiredResearchesCheck);
        $sm->get('logger')->log(\Zend\Log\Logger::INFO, 'required resources check : ' . $requiredResourcesCheck);
        $possessions = $colonyService->getBuildings()->getArrayCopy('building_id');
        $buildings   = $buildingService->getEntities()->getArrayCopy('id');
        $researches  = $researchesService->getEntities()->getArrayCopy('id');
        $resources   = $resourcesService->getResources()->getArrayCopy('id');

        $result = new ViewModel(
            array(
                'tick' => (string) $sm->get('Nouron\Service\Tick'),
                'ship' => $ship,
                'required_buildings_check' => $requiredBuildingsCheck,
                'required_resources_check' => $requiredResourcesCheck,
                'costs' => $personellService->getEntityCosts($shipId),
                'possessions' => $possessions,
                'buildings' => $buildings,
                'researches' => $researches,
                'resources' => $resources,
                'ap_available' => $personellService->getAvailableActionPoints('construction', $colonyId),
                'message' => $message,
            )
        );

        $result->setTerminal(true);
        return $result;
    }

    /**
     *
     * @return \Zend\View\Model\ViewModel
     */
    public function personellAction()
    {
        $entityId = $this->params()->fromRoute('id');
        $message = $this->params('message');

        $colonyId = $this->getActive('colony');
        $sm = $this->getServiceLocator();
        $resourcesService = $sm->get('Resources\Service\ResourcesService');
        $buildingService  = $sm->get('Techtree\Service\BuildingService');
        $personellService = $sm->get('Techtree\Service\PersonellService');
        $colonyService = $sm->get('Techtree\Service\ColonyService');
        $colonyService->setColonyId($colonyId);
        $techtree = $colonyService->getTechtree();
        $personell = $techtree['personell'][$entityId];

        $requiredBuildingsCheck = $personellService->checkRequiredBuildingsByEntityId($colonyId, $entityId);
        $costs = $personellService->getEntityCosts($entityId);
        $requiredResourcesCheck = $resourcesService->check($costs, $colonyId);
        $sm->get('logger')->log(\Zend\Log\Logger::INFO, 'required buildings check : ' . $requiredBuildingsCheck);
        $sm->get('logger')->log(\Zend\Log\Logger::INFO, 'required resources check : ' . $requiredResourcesCheck);
        $possessions = $colonyService->getBuildings()->getArrayCopy('building_id');
        $buildings   = $buildingService->getEntities()->getArrayCopy('id');
        $resources   = $resourcesService->getResources()->getArrayCopy('id');

        $result = new ViewModel(
            array(
                'tick' => (string) $sm->get('Nouron\Service\Tick'),
                'personell' => $personell,
                'required_buildings_check' => $requiredBuildingsCheck,
                'required_resources_check' => $requiredResourcesCheck,
                'costs' => $personellService->getEntityCosts($entityId),
                'possessions' => $possessions,
                'buildings' => $buildings,
                'resources' => $resources,
                'message' => $message,
            )
        );

        $result->setTerminal(true);
        return $result;
    }
}

