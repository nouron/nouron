<?php
namespace Techtree\Controller;

use Zend\View\Model\ViewModel;
use Techtree\Service\BuildingService;

class IndexController extends \Nouron\Controller\IngameController
{
    /**
     * Zeigt den Techtree an und ermoeglicht das Bauen und Forschen mithilfe
     * der Techtree-Build-Optionen
     */
    public function indexAction()
    {
        $sm = $this->getServiceLocator();
        $colonyId = $this->getActive('colony');
        $tick     = $sm->get('Nouron\Service\Tick');

        $colonyService   = $sm->get('Techtree\Service\ColonyService');
        $colonyService->setColonyId($colonyId);

        $buildingService = $sm->get('Techtree\Service\BuildingService');
        $buildings       = $buildingService->getEntities();
        $buildingCosts   = $buildingService->getEntityCosts();
        $colonyBuildings = $colonyService->getBuildings();

        $researchService = $sm->get('Techtree\Service\ResearchService');
        $researches      = $researchService->getEntities();
        $researchCosts   = $researchService->getEntityCosts();
        $colonyResearches = $colonyService->getResearches();

        $shipService     = $sm->get('Techtree\Service\ShipService');
        $ships           = $shipService->getEntities();
        $shipCosts       = $shipService->getEntityCosts();
        $colonyShips     = $colonyService->getShips();

        $personellService = $sm->get('Techtree\Service\PersonellService');
        $personell       = $personellService->getEntities();
        $personellCosts  = $personellService->getEntityCosts();
        $colonyPersonell = $colonyService->getPersonell();

        #$requirements = $gw->getRequirementsAsArray(null, "zindex_priority DESC");
        $techtree = $colonyService->getTechtree();

        $model =  new ViewModel(array(
            'buildings' => $buildings,
            'building_costs' => $buildingCosts,
            'researches' => $researches,
            'research_costs' => $researchCosts,
            'ships' => $ships,
            'personell' => $personell,
            'techtree' => $techtree,
            'possessions' => $this->resources()
        ));

        return $model;
    }
}

