<?php
namespace User\Controller;

use Zend\View\Model\ViewModel;

class ContactsController extends \Core\Controller\IngameController
{
    public function indexAction()
    {
        $sm = $this->getServiceLocator();
        return new ViewModel(array());
    }

}