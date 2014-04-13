<?php
namespace Nouron\Controller;

use Zend\EventManager\EventManagerInterface;
use Zend\Mvc\Controller\AbstractActionController;

/**
 * @method integer getActive(String $itemType)
 * @method integer getSelected(String $itemType)
 * @method array selectedIds()
 */
class IngameController extends AbstractActionController
{
#    public function setEventManager(EventManagerInterface $events)
#    {
#        parent::setEventManager($events);
#
#        $controller = $this;
#        $events->attach('dispatch', function ($e) use ($controller) {
#            if ($controller->zfcUserAuthentication()->hasIdentity()) {
#                $user  = $controller->zfcUserAuthentication()->getIdentity()->getArrayCopy();
#                #$_SESSION['userId'] = $user['id'];
#            } else {
#                return $controller->redirect()->toRoute('home');
#            }
#        }, 100); // execute before executing action logic
#    }
}