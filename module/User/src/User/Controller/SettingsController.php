<?php
namespace User\Controller;

use Laminas\View\Model\ViewModel;

class SettingsController extends \Core\Controller\IngameController
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