<?php
namespace Resources\Controller;

use Zend\View\Model\JsonModel;
use Zend\View\Model\ViewModel;
use Resources\Service\Gateway;

/**
 *
 * @author tt
 *
 */
class JsonController extends \Nouron\Controller\IngameController
{
    /**
     *
     */
    public function getColonyResourcesAction()
    {
        $sm = $this->getServiceLocator();
        $gw = $sm->get('Resources\Service\Gateway');

        $colonyId = $this->params()->fromRoute('id');
        $coloResources = $gw->getColonyResources(array("colony_id" => $colonyId))->getArrayCopy('resource_id');
        return new JsonModel($coloResources);
    }

    /**
     *
     */
    public function getResourcesAction()
    {
        $sm = $this->getServiceLocator();
        $gw = $sm->get('Resources\Service\Gateway');
        $resources = $gw->getResources()->getArrayCopy('id');

        foreach ($resources as $id => $res) {
            $resources[$id]['name'] = $sm->get('translator')->translate( $res['name'] );
        }

        return new JsonModel($resources);
    }
}

