<?php
namespace INNN\Controller;

use Zend\View\Model\ViewModel;
use Zend\View\Model\JsonModel;

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
        $userService = $sm->get('User\Service\User');

        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost();
            $form->setData($data);
            if ($form->isValid()) {
                $data = $form->getData(); // replace data with filtered data
                $user = $userService->getUserByName(trim($data['recipient']));
                print_r($user);
                $data['recipient_id'] = $user['user_id'];
                $data['sender_id']    = $_SESSION['userId'];
                $result = $messageService->sendMessage($data);
                if ( $result ) {
                    $this->flashMessenger()->setNamespace('success')->addMessage('Successfull!');
                    print('TTTTTTTTTTTTTTTTTTTTTTTTTTTT');
                    return $this->redirect()->toRoute('innn/message', array('action'=>'new'));
                } else {
                    print('FALSE');

                    $this->flashMessenger()->setNamespace('success')->addMessage('Anything was wrong!');
                    return $this->redirect()->toRoute('innn/message', array('action'=>'new'));
                }
            }
        }

        return new ViewModel(
            array(
                'form' => $form,
                'flashMessages' => $this->flashMessenger()->getMessages()
            )
        );
    }

    public function removeAction()
    {
        $id = $this->params('id');

        $sm = $this->getServiceLocator();
        $msgService = $sm->get('INNN\Service\Message');

        // check if recipient is current user
        $message = $msgService->getMessage($id);
        if ($message && $message['recipient_id'] == $_SESSION['userId']) {
            $result = $msgService->setMessageStatus($id, 'deleted');
        } else {
            $result = false;
        }

        return new JsonModel(array(
            'result' => $result
        ));
    }

    public function reactAction()
    {
        $messageId = $this->params('id');
        $reactionType = $this->params('type');
        $result = $this->react($reactionType, $messageId, false);
        return new JsonModel(array(
            'result' => $result
        ));
    }

    public function respondAction()
    {
        $messageId = $this->params('id');
        $reactionType = $this->params('type');
        $result = $this->react($reactionType, $messageId, true);

        if ($result) {
            // redirect to innn/message/new with given recipient id
            $this->redirect()->toRoute('innn/message', array('action'=>'new', 'recipient_id' => $message['sender_id']));
        } else {
            return new JsonModel(array(
                'result' => $result
            ));
        }
    }

    /**
     *
     * @param string|null $type  'positive' OR 'negative'
     * @param numeric $messageId
     * @param string $respond
     */
    public function react($type=null, $messageId)
    {
        // $type is not relevant for now, @TODO: implement later

        $sm = $this->getServiceLocator();
        $msgService = $sm->get('INNN\Service\Message');

        // check if recipient is current user
        $message = $msgService->getMessage($messageId);
        if ($message && $message['recipient_id'] == $_SESSION['userId']) {
            // setting message status is only allowed for recipient
            $result = $msgService->setMessageStatus($messageId, 'read');
        } else {
            $result = false;
        }
        return $result;
    }


//     public function getMessagesAsJson()
//     {
//         $sm = $this->getServiceLocator();
//         $gw = $sm->get('INNN\Service\Gateway');
//         return new JsonModel( $gw->getMessagesAsArray() );
//     }

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
