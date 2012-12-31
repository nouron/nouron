<?php
namespace Techtree\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Techtree\Service\Gateway;

class IndexController extends AbstractActionController
{
    /**
     * Auswertung der uebergebenen Parameter, Auslesen der Techdaten aus der DB
     * und Berechnung der Grunddaten die sowohl fuer die klassische Variante des
     * Techtrees als auch fuer die Techtree-Build-Variante noetig sind!
     *
     * @param string $type    OPTIONAL Gewaehlter Typcode
     * @param string $purpose OPTIONAL Gewaehlter Zweckcode
     */
    function init()
    {
        parent::init();

        // Auswertung der uebergebenen Parameter
        $selectedType    = $this->_params->type;
        $selectedPurpose = $this->_params->purpose;

        // falls Parameter fehlen gelten diese Standardwerte:
        $selectedType = empty($selectedType) ? $selectedType=0 : $selectedType;  //  0  = alle Techtypen anzeigen
        $selectedPurpose = empty($selectedPurpose) ? $selectedPurpose = 'a' : $selectedPurpose; // 'a' = Techs fuer alle Zwecke anzeigen
    }

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
        $requirements = $gw->getRequirements()->toArray(array('tech_id','required_tech_id'));

        $techtree = $gw->getTechtreeByColonyId($colonyId);
        //$costs = $gw->getTechCosts();
        $orders = $gw->getOrders();

        $model =  new ViewModel(
            array(
                'techs' => $techs,
                'techtree' => $techtree,
                'requirements' => $requirements,
                //'techgraph_infos' => $this->getTechGraphInfos($colonyId),
                //'costs' => $this->getTechCosts
                'orders' => $orders,
            )
        );

        //\Zend\Debug\Debug::dump($model);

        return $model;

//         // Technologien und TechVoraussetzungen an die View uebergeben
//         $techtree = $this->techtreeGateway->getGraphicalTechtreeByColonyId($colonyId);

//         $this->view->assign($techtree);

//         $orders = $this->techtreeGateway->getOrders("nColony = $colonyId AND nTick = $tick");
//         $maxTechOrders = $this->techtreeGateway->getMaxBuildingOrders($colonyId);

//         if ($orders->count() >= $maxTechOrders ) {
//             $this->view->maxOrdersReached = true;
//         }

        // @TODO: max Research Orders

    }

    //    /**
    //     * erhoeht den Besitz der Technologie um 1, wenn maximale Anzahl noch nicht getecht wurde
    //     */
    //    public function levelupAction()
    //    {
    //        $colonyId    = Zend_Registry::get('colonyId');
    //        $displayType = $this->_params->display;
    //        $techId      = $this->_params->tid;
    //
    //        try {
    //            $tick = Zend_Registry::get("Tick");
    //            $orders = $this->techtreeGateway->getOrders("nColony = $colonyId AND nTick = $tick");
    //            $maxTechOrders = $this->techtreeGateway->getMaxBuildingOrders($colonyId);
    //
    //            if ($orders->count() >= $maxTechOrders ) {
    //                throw new Exception('max_orders_reached');
    //            }
    //
    //            $this->techtreeGateway->technologyLevelUp($techId, $colonyId);
    //            $this->_flashMessenger->setNamespace('success')->addMessage('order_confirmed');
    //        } catch (Exception $e) {
    //            $this->_flashMessenger->setNamespace('error')->addMessage('order_denied' . $e->getMessage());
    //        }
    //
    //        $this->_redirect('techtree/index2/'.$displayType);
    //    }

    //    /**
    //     * verringert den Besitz der Technologie um 1, wenn mindestens 1 vorhanden
    //     *
    //     */
    //    public function leveldownAction()
    //    {
    //        $colonyId = Zend_Registry::get('colonyId');
    //
    //        // collect the data from the user
    //        $displayType = $this->_params->display;
    //        $techId      = $this->_params->tid;
    //
    //        try {
    //            $tick = Zend_Registry::get("Tick");
    //            $orders = $this->techtreeGateway->getOrders("nColony = $colonyId AND nTick = $tick");
    //            $maxTechOrders = $this->techtreeGateway->getMaxBuildingOrders($colonyId);
    //
    //            if ($orders->count() >= $maxTechOrders ) {
    //                throw new Exception('max_orders_reached');
    //            }
    //
    //            $this->techtreeGateway->technologyLevelDown($techId, $colonyId);
    //            $this->_flashMessenger->setNamespace('success')->addMessage('order_confirmed');
    //        } catch (Exception $e) {
    //            $this->_flashMessenger->setNamespace('error')->addMessage($e->getMessage());
    //        }
    //
    //        $this->_redirect('techtree/index2/'.$displayType);
    //    }
    //
    //    /**
    //     * cancelt eine Order
    //     *
    //     */
    //    public function cancelAction()
    //    {
    //        $colonyId = Zend_Registry::get('colonyId');
    //
    //        // collect the data from the user
    //        $displayType = $this->_params->display;
    //        $techId      = $this->_params->tid;
    //
    //        try {
    //            $this->techtreeGateway->cancelOrder($techId, $colonyId);
    //            $this->_flashMessenger->setNamespace('success')->addMessage('order_cancelled');
    //        } catch (Exception $e) {
    //            $this->_flashMessenger->setNamespace('error')->addMessage($e->getMessage());
    //        }
    //
    //        $this->_redirect('techtree/index2/'.$displayType);
    //    }
}

