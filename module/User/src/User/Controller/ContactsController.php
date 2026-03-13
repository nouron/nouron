<?php
namespace User\Controller;

use Laminas\View\Model\ViewModel;

class ContactsController extends \Core\Controller\IngameController
{
    public function indexAction()
    {
        $sm = $this->getServiceLocator();
        return new ViewModel(array());
    }

}