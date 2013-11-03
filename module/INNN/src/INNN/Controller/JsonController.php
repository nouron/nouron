<?php
namespace INNN\Controller;

use Zend\View\Model\JsonModel;
use Zend\View\Model\ViewModel;
use Techtree\Service\BuildingService;

class JsonController extends \Nouron\Controller\IngameController
{
    public function getMessagesAction()
    {
        $sm = $this->getServiceLocator();
        $gw = $sm->get('INNN\Service\Gateway');
        return new JsonModel( $gw->getMessagesAsArray() );
    }
}

