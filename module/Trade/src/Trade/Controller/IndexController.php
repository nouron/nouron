<?php
namespace Trade\Controller;

use Zend\View\Model\ViewModel;
use Trade\Service\Gateway;

class IndexController extends \Nouron\Controller\IngameController
{
    public function technologiesAction()
    {
        $sm = $this->getServiceLocator();

        $sm->setService('colonyId', 0); // TODO: get colonyId via controller plugin or session

        $colonyId = $sm->get('colonyId');
        $tick     = $sm->get('Nouron\Service\Tick');

        $gw = $sm->get('Trade\Service\Gateway');
        $techs = $gw->getTechnologies();

        return new ViewModel( array(
            'technologies' => $techs
        ) );
    }

    public function resourcesAction()
    {
        $sm = $this->getServiceLocator();

        $sm->setService('colonyId', 0); // TODO: get colonyId via controller plugin or session

        $colonyId = $sm->get('colonyId');
        $tick     = $sm->get('Nouron\Service\Tick');

        $gw = $sm->get('Trade\Service\Gateway');
        $resources = $gw->getResources();

        return new ViewModel( array(
            'resources' => $resources
        ) );
    }
}

