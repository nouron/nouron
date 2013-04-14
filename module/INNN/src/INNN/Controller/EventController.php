<?php
namespace INNN\Controller;

use Zend\View\Model\ViewModel;

class EventController extends \Nouron\Controller\IngameController
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