<?php
namespace User\Controller;

use Zend\View\Model\ViewModel;

class SettingsController extends \Nouron\Controller\IngameController
{
    public function indexAction()
    {
        $sm = $this->getServiceLocator();
        return new ViewModel(array());
    }

    public function setPasswordAction()
    {

    }

    public function setNameAction()
    {

    }

}