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
        $messages = $messageService->getInboxMessages(3);

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
        $messages = $messageService->getOutboxMessages(3);

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
        //$userService = $sm->get('User\Service\User');
//         $entity = new \INNN\Entity\Message();
//         $form->bind($entity);

        if ($this->getRequest()->isPost()) {
            $form->setData($this->getRequest()->getPost());
            if ($form->isValid()) {
                $newEntity = $form->getData();
                $messageId = $messageService->sendMessage($newEntity);
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
        $messages = $messageService->getArchivedMessages(3);

        return new ViewModel(
            array(
                'messages' => $messages
            )
        );
    }
}
