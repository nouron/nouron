<?php
namespace Galaxy\Controller;

#use Laminas\View\Model\ViewModel;
use Laminas\View\Model\JsonModel;

class JsonController extends \Core\Controller\IngameController
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

            if (is_numeric($x2) && is_numeric($y2)) {
                $x = round(($x+$x2)/2);
                $y = round(($y+$y2)/2);
            }

            $sm = $this->getServiceLocator();
            $galaxyService = $sm->get('Galaxy\Service\Gateway');

            $fleets   = $galaxyService->getByCoordinates('fleets', array($x, $y));
            $objects  = $galaxyService->getByCoordinates('objects', array($x, $y));

            $data = array();

            // Field types (asteroid, minefield, nebula variants, graveyard) → misc layer (0)
            // Solid bodies (planets, giants) → planets layer (1)
            $fieldTypes = [9, 10, 11, 14, 15, 16];
            foreach ($objects as $object) {
                if (in_array($object->getTypeId(), $fieldTypes)) {
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
        catch (\Throwable $e)
        {
            return new JsonModel(array('error' => $e->getMessage()));
        }

    }
}

