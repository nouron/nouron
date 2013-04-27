<?php
namespace INNN\Controller;

use Zend\View\Model\ViewModel;

class MessageController extends \Nouron\Controller\IngameController
{
    /**
     *
     * @return \Zend\View\Model\ViewModel
     */
    public function inboxAction()
    {
        $sm = $this->getServiceLocator();
        $messageService = $sm->get('INNN\Service\Message');
        $messages = $messageService->getInboxMessages($_SESSION['userId']);

        return new ViewModel(
            array(
                'messages' => $messages
            )
        );
    }

    /**
     *
     * @return \Zend\View\Model\ViewModel
     */
    public function outboxAction()
    {
        $sm = $this->getServiceLocator();
        $messageService = $sm->get('INNN\Service\Message');
        $messages = $messageService->getOutboxMessages($_SESSION['userId']);

        return new ViewModel(
            array(
                'messages' => $messages
            )
        );
    }

    /**
     *
     * @return \Zend\View\Model\ViewModel
     */
    public function newAction()
    {
        $sm = $this->getServiceLocator();
        $form = new \INNN\Form\Message();
        $messageService = $sm->get('INNN\Service\Message');

        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost();
            $form->setData($data);
            if ($form->isValid()) {
                $data = $form->getData(); // replace data with filtered data
                $messageId = $messageService->sendMessage($data);
                \Zend\Debug\Debug::dump($messageId);
            }
        }

        return new ViewModel(
            array(
                'form' => $form
            )
        );
    }

    /**
     *
     * @return \Zend\View\Model\ViewModel
     */
    public function replyAction()
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
        $sm = $this->getServiceLocator();
        $messageService = $sm->get('INNN\Service\Message');
        $messages = $messageService->getArchivedMessages($_SESSION['userId']);

        return new ViewModel(
            array(
                'messages' => $messages
            )
        );
    }
}
