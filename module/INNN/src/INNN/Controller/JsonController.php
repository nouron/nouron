<?php
namespace INNN\Controller;

use Zend\View\Model\JsonModel;

class JsonController extends \Core\Controller\IngameController
{
    public function getMessagesAction()
    {
        $sm = $this->getServiceLocator();
        $gw = $sm->get('INNN\Service\Gateway');
        return new JsonModel( $gw->getMessagesAsArray() );
    }
}

