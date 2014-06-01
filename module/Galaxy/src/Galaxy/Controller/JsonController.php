<?php
namespace Galaxy\Controller;

#use Zend\View\Model\ViewModel;
use Zend\View\Model\JsonModel;

class JsonController extends \Nouron\Controller\IngameController
{
    public function getmapdataAction()
    {
        try {
            $x = $this->params()->fromRoute('x');
            $y = $this->params()->fromRoute('y');

            if (!is_numeric($x) || !is_numeric($y)) {
                return new JsonModel(array(
                    'error' => 'unknown destination',
                    'x' => $x,
                    'y' => $y
                ));
            }

            $sm = $this->getServiceLocator();
            $galaxyService = $sm->get('Galaxy\Service\Gateway');

            $fleets   = $galaxyService->getByCoordinates('fleets', array($x, $y));
            $colonies = $galaxyService->getByCoordinates('colonies', array($x, $y));
            $objects  = $galaxyService->getByCoordinates('objects', array($x, $y));

            $data = array();

            foreach ($fleets as $fleet) {
                $data[] = array(
                    "layer" => 2,
                    "x" => $fleet->getX(),
                    "y" => $fleet->getY(),
                    "attribs" => array(
                        "title" => $fleet->getFleet(),
                        "class" => ""
                    )
                );
            }

            foreach ($colonies as $colony) {
                $data[] = array(
                    "layer" => 1,
                    "x" => $colony->getX(),
                    "y" => $colony->getY(),
                    "attribs" => array(
                        "title" => $colony->getName(),
                        "class" => ""
                    )
                );
            }

            foreach ($objects as $object) {
                $data[] = array(
                    "layer" => 0,
                    "x" => $object->getX(),
                    "y" => $object->getY(),
                    "attribs" => array(
                        "title" => $object->getName(),
                        "class" => ""
                    )
                );
            }

            return new JsonModel($data);
        }
        catch (\Exception $e)
        {
            return new JsonModel(array('error' => $e->getMessage()));
        }

    }
}

