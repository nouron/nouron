<?php
namespace Galaxy\Controller;

use Zend\View\Model\ViewModel;

class IndexController extends \Core\Controller\IngameController
{
    public function indexAction()
    {
        $sm = $this->getServiceLocator();

        $tick     = $sm->get('Core\Service\Tick');

        $gw = $sm->get('Galaxy\Service\Gateway');
        $systems = $gw->getSystems()->getArrayCopy('id');

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

