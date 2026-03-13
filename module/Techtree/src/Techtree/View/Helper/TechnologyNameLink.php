<?php
namespace Techtree\View\Helper;

use Laminas\View\Helper\AbstractHelper;
use Techtree\Service\BuildingService;

class TechnologyNameLink extends AbstractHelper
{
    /** @var BuildingService */
    protected $buildingService;

    public function __construct(BuildingService $buildingService)
    {
        $this->buildingService = $buildingService;
    }

    /**
     * @param  int $techId
     * @param  int $colonyId OPTIONAL
     * @return string
     */
    public function __invoke($techId, $colonyId = null)
    {
        $gateway = $this->buildingService;
        $tech = $gateway->getTechnology($techId);
        if (is_numeric($colonyId)) {
            $lvl = '(' . $gateway->getLevelByTechnologyId($techId, $colonyId) . ')';
        } else {
            $lvl = '';
        }
        $xhtml  = '<span class="'.$tech->type.'">';
        $xhtml .= '<a href="' . $this->view->baseUrl() . '/techtree/index" >';
        $xhtml .= '<b>' . $this->view->translate($tech->name) ." $lvl</b></a>";
        $xhtml .= '</span>';
        return $xhtml;
    }
}
