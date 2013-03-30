<?php
namespace INNN\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Techtree\Service\Gateway;

class ArchiveController extends MessageController
{
    public function indexAction()
    {
        $sm = $this->getServiceLocator();

        return new ViewModel(
            array(

            )
        );
    }

    public function deleteAction()
    {
        return new JsonModel(array());
    }
}