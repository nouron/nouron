<?php
namespace Fleets\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;


class IndexController extends AbstractActionController
{
    public function indexAction()
    {
        $sm = $this->getServiceLocator();

        $tick     = $sm->get('Nouron\Service\Tick');

        $gw = $sm->get('Fleets\Service\Gateway');

        return new ViewModel(
            array(

            )
        );

    }
}

