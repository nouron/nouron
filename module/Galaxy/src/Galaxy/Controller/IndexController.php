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

        $tick     = $sm->get('Nouron\Service\Tick');

        $gw = $sm->get('Galaxy\Service\Gateway');
        $systems = $gw->getSystems()->toArray('id');

        $config = $sm->get('Config');
        $config = $config['galaxy_view_config'];

        return new ViewModel(
            array(
                'systems' => $systems,
                'config' => $config
            )
        );

    }
}

