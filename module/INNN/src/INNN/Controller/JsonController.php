<?php
namespace INNN\Controller;

use Zend\View\Model\JsonModel;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Techtree\Service\Gateway;

class JsonController extends AbstractActionController
{
    public function getMessagesAction()
    {
        $sm = $this->getServiceLocator();
        $gw = $sm->get('INNN\Service\Gateway');
        return new JsonModel( $gw->getMessagesAsArray() );
    }
}

