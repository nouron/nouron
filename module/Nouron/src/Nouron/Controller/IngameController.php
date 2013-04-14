<?php
namespace Nouron\Controller;

use Zend\EventManager\EventManagerInterface;
use Zend\Mvc\Controller\AbstractActionController;

class IngameController extends AbstractActionController
{
    public function setEventManager(EventManagerInterface $events)
    {
        parent::setEventManager($events);

        $controller = $this;
        $events->attach('dispatch', function ($e) use ($controller) {
//             $request = $e->getRequest();
//             $method  = $request->getMethod();
            if ($controller->zfcUserAuthentication()->hasIdentity()) {
                $user  = $controller->zfcUserAuthentication()->getIdentity();
                print_r($user);
                $state = $user->getState();
            } else {
                return $controller->redirect()->toRoute('home');
            }
        }, 100); // execute before executing action logic
    }
}