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
            $x2 = $this->params()->fromRoute('x2');
            $y2 = $this->params()->fromRoute('y2');

            if (!is_numeric($x) || !is_numeric($y)) {
                return new JsonModel(array(
                    'error' => 'unknown destination',
                    'x' => $x,
                    'y' => $y
                ));
            }

            if (is_numeric($x2, $y2)) {
                $x = round(($x+$x2)/2);
                $y = round(($y+$y2)/2);
            }

            $sm = $this->getServiceLocator();
            $galaxyService = $sm->get('Galaxy\Service\Gateway');

            $fleets   = $galaxyService->getByCoordinates('fleets', array($x, $y));
            $objects  = $galaxyService->getByCoordinates('objects', array($x, $y));

            $data = array();

            foreach ($objects as $object) {
                if ($object->getTypeId() == 9) {
                    $layer = 0;
                } else {
                    $layer = 1;
                }
                $data[] = array(
                    "layer" => $layer,
                    "x" => $object->getX(),
                    "y" => $object->getY(),
                    "attribs" => array(
                        "title" => $object->getName(),
                        "class" => $object->getType(),
                        "image_url" => $object->getImageUrl()
                    )
                );
            }

            foreach ($fleets as $fleet) {
                $data[] = array(
                    "layer" => 3,
                    "x" => $fleet->getX(),
                    "y" => $fleet->getY(),
                    "attribs" => array(
                        "title" => $fleet->getFleet(),
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

