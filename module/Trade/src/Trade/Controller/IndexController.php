<?php
namespace Trade\Controller;

use Zend\View\Model\ViewModel;
use Trade\Service\Gateway;
use Techtree\Entity\Technology;

class IndexController extends \Nouron\Controller\IngameController
{
    public function technologiesAction()
    {
        $sm = $this->getServiceLocator();

        $sm->setService('colonyId', 0); // TODO: get colonyId via controller plugin or session

        $colonyId = $sm->get('colonyId');
        $tick     = $sm->get('Nouron\Service\Tick');

        $gw = $sm->get('Trade\Service\Gateway');
        $techOffers = $gw->getTechnologies();

        $techGw = $sm->get('Techtree\Service\Gateway');
        $techs = $techGw->getTechnologies();
        $form = new \Trade\Form\SearchForm('technologies', $techs->getArrayCopy('id'));
        $tech = new Technology();
        $form->bind($tech);

        $tradeService = $sm->get('Trade\Service\Gateway');
        $userService = $sm->get('User\Service\User');

         $request = $this->getRequest();
         if ($request->isPost()) {
             $form->setData($request->getPost());

             if ($form->isValid()) {
                 var_dump($tech);
             }
         }

        return new ViewModel( array(
            'searchForm' => $form,
            'technologies' => $techOffers
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

