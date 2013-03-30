<?php
namespace INNN\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Techtree\Service\Gateway;

class MessageController extends AbstractActionController
{
    public function inboxAction()
    {
        $sm = $this->getServiceLocator();

        return new ViewModel(
            array(

            )
        );
    }

    public function outboxAction()
    {
        $sm = $this->getServiceLocator();

        return new ViewModel(
            array(

            )
        );
    }

    public function newAction()
    {
        $sm = $this->getServiceLocator();

        return new ViewModel(
            array(

            )
        );
    }

    public function getMessagesAsJson()
    {
        $sm = $this->getServiceLocator();
        $gw = $sm->get('INNN\Service\Gateway');
        return new JsonModel( $gw->getMessagesAsArray() );
    }

    public function answerAction()
    {
        $this->createAction();

        return new ViewModel(
                array(

                )
        );
    }

    public function archiveAction()
    {
        return new ViewModel(array());
    }
}
