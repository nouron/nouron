<?php
namespace Galaxy\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
//use Galaxy\Service\Gateway;

class IndexController extends AbstractActionController
{
    public function indexAction()
    {
        $sm = $this->getServiceLocator();

        $sm->setService('colonyId', 0);
        $sm->setService('tick', 12345);

        $colonyId = $sm->get('colonyId');
        $tick     = $sm->get('Tick');

//         $gw = $sm->get('Techtree\Service\Gateway');
//         $techs = $gw->getTechnologies();
        return new ViewModel(
                array(

                )
        );

    }

}

