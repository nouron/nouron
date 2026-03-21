<?php

namespace Application\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;

class IndexController extends AbstractActionController
{
    public function indexAction()
    {
//         $this->getServiceLocator()->get('logger')->log(\Laminas\Log\Logger::INFO, 'message');
        return new ViewModel();
    }

}
