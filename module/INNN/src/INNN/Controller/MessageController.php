<?php
namespace INNN\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Techtree\Service\Gateway;

class IndexController extends AbstractActionController
{

    public function indexAction()
    {
        $sm = $this->getServiceLocator();

        return new ViewModel(
            array(

            )
        );


    }

}

