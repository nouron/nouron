<?php
namespace INNN\Controller;

use Zend\View\Model\ViewModel;
use Zend\View\Model\JsonModel;
use Zend\Session\Container;

/**
 * @method integer getActive(String $itemType)
 * @method integer getSelected(String $itemType)
 * @method array selectedIds()
 */
class MessageController extends \Nouron\Controller\IngameController
{
    /**
     *
     * @return \Zend\View\Model\ViewModel
     */
    public function eventsAction()
    {
        $sm = $this->getServiceLocator();
        $messageService = $sm->get('INNN\Service\EventService');
        $messages = $messageService->getEvents($this->getActive('user'));

        return new ViewModel(array(
            'messages' => $messages
        ));
    }

    /**
     *
     * @return \Zend\View\Model\ViewModel
     */
    public function inboxAction()
    {
        $sm = $this->getServiceLocator();
        $messageService = $sm->get('INNN\Service\MessageService');
        $messages = $messageService->getInboxMessages($this->getActive('user'));

        return new ViewModel(array(
            'messages' => $messages
        ));
    }

    /**
     *
     * @return \Zend\View\Model\ViewModel
     */
    public function outboxAction()
    {
        $sm = $this->getServiceLocator();
        $messageService = $sm->get('INNN\Service\MessageService');
        $messages = $messageService->getOutboxMessages($this->getActive('user'));

        return new ViewModel(array(
            'messages' => $messages
        ));
    }

    /**
     *
     * @return \Zend\View\Model\ViewModel
     */
    public function newAction()
    {
        $sm = $this->getServiceLocator();
        $form = new \INNN\Form\Message();
        #$form->setAttribute('action', '/messages/new');
        $form->setAttribute('method', 'post');

        $messageService = $sm->get('INNN\Service\MessageService');
        $userService = $sm->get('User\Service\UserService');

        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost();
            $form->setData($data);
            if ($form->isValid()) {
                $data = $form->getData(); // replace data with filtered data
                $user = $userService->getUserByName(trim($data['recipient']));
                if (empty($user)) {
                    // TODO: Flash Messenger not working correctly yet!!!!!
                    $this->flashMessenger()->setNamespace('error')->addMessage('Error: Recipient not found.');
                    return $this->redirect()->toRoute('messages', array('action'=>'new'));
                }
                $session = new Container('activeIds');
                $data['recipient_id'] = $user['user_id'];
                $data['sender_id']    = $session->userId;
                $result = $messageService->sendMessage($data);
                if ( $result ) {
                    $this->flashMessenger()->setNamespace('success')->addMessage('Successfull!');
                    return $this->redirect()->toRoute('messages', array('action'=>'new'));
                } else {
                    $this->flashMessenger()->setNamespace('success')->addMessage('Anything was wrong!');
                    return $this->redirect()->toRoute('messages', array('action'=>'new'));
                }
            }
        }

        return new ViewModel(array(
                'form' => $form,
                'flashMessages' => $this->flashMessenger()->getMessages()
            )
        );
    }

    public function reactAction()
    {
        $messageId = $this->params()->fromQuey('id');
        $reactionType = $this->params()->fromQuey('type');
        $result = $this->react($reactionType, $messageId, false);
        return new JsonModel(array(
            'result' => $result,
            'status' => 'read'
        ));
    }

    public function respondAction()
    {
        $messageId = $this->params()->fromQuey('id');
        $reactionType = $this->params()->fromQuey('type');
        $result = $this->react($reactionType, $messageId);

        $sm = $this->getServiceLocator();
        $messageService = $sm->get('INNN\Service\MessageService');
        $message = $messageService->getMessage($messageId);
        if ($result) {
            // redirect to messages//new with given recipient id
            $this->redirect()->toRoute('messages/', array(
                'action'=>'new',
                'recipient_id' => $message->getSenderId()
            ));
        } else {
            return new JsonModel(array(
                'result' => $result,
                'status' => 'read'
            ));
        }
    }

    /**
     *
     * @param string|null $type  'positive' OR 'negative'
     * @param numeric $messageId
     */
    public function react($type=null, $messageId)
    {
        // $type is not relevant for now, @TODO: implement later

        return true; #$this->setMessageStatus($messageId, 'read');
    }

    /**
     *
     * @return \Zend\View\Model\JsonModel|\Zend\View\Model\ViewModel
     */
    public function archiveAction()
    {
        $messageId = $this->params()->fromQuey('id');
        if (!empty($messageId)) {
            // archive the given message
            return new JsonModel(array(
                'result' => $this->setMessageStatus($messageId, 'archived'),
                'status' => 'archived'
            ));
        } else {
            // show archived messages
            $sm = $this->getServiceLocator();
            $messageService = $sm->get('INNN\Service\MessageService');
            $messages = $messageService->getArchivedMessages($_SESSION['userId']);
            return new ViewModel(array(
                'messages' => $messages
            ));
        }
    }

    /**
     *
     * @return \Zend\View\Model\JsonModel
     */
    public function removeAction()
    {
        $messageId = $this->params()->fromQuey('id');
        return new JsonModel(array(
            'result' => $this->setMessageStatus($messageId, 'deleted'),
            'status' => 'deleted'
        ));
    }

    /**
     *
     * @param  numeric $messageId
     * @param  string  $status  'read'|'archived'|'deleted'
     * @return boolean
     */
    protected function setMessageStatus($messageId, $status)
    {
        $sm = $this->getServiceLocator();
        $messageService = $sm->get('INNN\Service\MessageService');

        $result = false;
        if (!empty($messageId)) {
            $message = $messageService->getMessage($messageId);
            if ($message && $message->getRecipientId() == $_SESSION['userId']) {
                // setting message status is only allowed for recipient
                $result = $messageService->setMessageStatus($messageId, $status);
            }
        }
        return $result;
    }
}
