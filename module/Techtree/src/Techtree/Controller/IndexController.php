<?php
namespace Techtree\Controller;

use Zend\View\Model\ViewModel;
use Techtree\Service\Gateway;

class IndexController extends \Nouron\Controller\IngameController
{
    /**
     * Zeigt den Techtree an und ermoeglicht das Bauen und Forschen mithilfe der Techtree-Build-Optionen
     */
    public function indexAction()
    {
        $sm = $this->getServiceLocator();

        $sm->setService('colonyId', 0); // TODO: get colonyId via controller plugin or session

        $colonyId = $sm->get('colonyId');
        $tick     = $sm->get('Nouron\Service\Tick');

        $gw = $sm->get('Techtree\Service\Gateway');
        $techs = $gw->getTechnologies();
        $requirements = $gw->getRequirementsAsArray(null, "zindex_priority DESC");

        $techtree = $gw->getTechtreeByColonyId($colonyId);
        //$costs = $gw->getTechCosts();

        $model =  new ViewModel(array(
                'techs' => $techs,
                'techtree' => $techtree,
                'requirements' => $requirements,
                'possessions' => $this->resources()
        ));

        return $model;
    }
}

