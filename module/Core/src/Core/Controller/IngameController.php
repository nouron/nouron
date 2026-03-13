<?php

/**
 * @package   Nouron_Core
 * @category  Controller
 */

namespace Core\Controller;

use Laminas\EventManager\EventManagerInterface;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\ServiceManager\ServiceLocatorInterface;

/**
 * @method integer getActive(String $itemType)
 * @method integer getSelected(String $itemType)
 * @method array selectedIds()
 */
class IngameController extends AbstractActionController
{
    /**
     * Compatibility shim: Laminas removed getServiceLocator() from AbstractController.
     * Retrieve the service manager via the MVC event application.
     *
     * @return ServiceLocatorInterface
     */
    public function getServiceLocator()
    {
        return $this->getEvent()->getApplication()->getServiceManager();
    }

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
