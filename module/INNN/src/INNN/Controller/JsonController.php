<?php
namespace INNN\Controller;

use Zend\View\Model\JsonModel;

class JsonController extends \Nouron\Controller\IngameController
{
    public function getMessagesAction()
    {
        $sm = $this->getServiceLocator();
        $gw = $sm->get('INNN\Service\Gateway');
        return new JsonModel( $gw->getMessagesAsArray() );
    }
}

