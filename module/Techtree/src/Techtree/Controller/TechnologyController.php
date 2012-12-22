<?php
namespace Techtree\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Techtree\Service\Gateway;

class TechnologyController extends AbstractActionController
{
    /**
     * Zeigt den Techtree an und ermoeglicht das Bauen und Forschen mithilfe der Techtree-Build-Optionen
     */
    public function indexAction()
    {
        $sm = $this->getServiceLocator();

        $sm->setService('colonyId', 0);
        $sm->setService('tick', 12345);

        $colonyId = $sm->get('colonyId');
        $tick     = $sm->get('Tick');

        $gw = $sm->get('Techtree\Service\Gateway');
        $techs = $gw->getTechnologies();
        $techtree = $gw->getTechtreeByColonyId($colonyId);

        $this->flashMessenger()->addMessage('Thank you for your comment!');

        return new ViewModel(
            array(
                'technologies' => $techs,
                'techtree' => $techtree,
                //'techgraph_infos' => $this->getTechGraphInfos($colonyId),
                //'costs' => $this->getTechCosts
                'flashMessages' => $this->flashMessenger()->getMessages()
            )
        );



        // Technologien und TechVoraussetzungen an die View uebergeben
        $techtree = $this->techtreeGateway->getGraphicalTechtreeByColonyId($colonyId);

        $this->view->assign($techtree);

        $orders = $this->techtreeGateway->getOrders("nColony = $colonyId AND nTick = $tick");
        $maxTechOrders = $this->techtreeGateway->getMaxBuildingOrders($colonyId);

        if ($orders->count() >= $maxTechOrders ) {
            $this->view->maxOrdersReached = true;
        }

        // @TODO: max Research Orders

    }
}

