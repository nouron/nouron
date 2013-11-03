<?php
namespace Resources\Controller;

use Zend\View\Model\JsonModel;
use Zend\View\Model\ViewModel;
use Resources\Service\ResourcesService;

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
        $gw = $sm->get('Resources\Service\ResourcesService');

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
        $gw = $sm->get('Resources\Service\ResourcesService');
        $resources = $gw->getResources()->getArrayCopy('id');

        foreach ($resources as $id => $res) {
            $resources[$id]['name'] = $sm->get('translator')->translate( $res['name'] );
        }

        return new JsonModel($resources);
    }

    /**
     *
     */
    public function reloadresourcebarAction()
    {

        $sm = $this->getServiceLocator();
        $sm->setService('colonyId', 1); // TODO: get colonyId via controller plugin or session
        $colonyId = $this->getActive('colony'); # for correct service init
        $result = new ViewModel(
            array(
                'tick' => (string) $sm->get('Nouron\Service\Tick'),
                'possessions' => $this->resources(),
            )
        );

        $result->setTerminal(true);
        return $result;
    }
}

