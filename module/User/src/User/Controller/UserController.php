<?php
namespace User\Controller;

use Zend\View\Model\ViewModel;

class UserController extends \Core\Controller\IngameController
{
    public function userAction()
    {
        $sm = $this->getServiceLocator();
        return new ViewModel(array());
    }
}