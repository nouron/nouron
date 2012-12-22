<?php
namespace INNN\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Techtree\Service\Gateway;

class EventController extends AbstractActionController
{
    public function indexAction()
    {
        $sm = $this->getServiceLocator();
    }
}