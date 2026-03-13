<?php
namespace Resources\View\Helper;

use Laminas\View\Helper\AbstractHelper;

class Resources extends AbstractHelper
{
    /**
     * @return string
     */
    public function __invoke($possessions)
    {
        $xhtml = '<div class="row resource-bar">';
        foreach ($possessions as $resId => $resource):
            $name = $resource['name'];
            $abbreviation =  $resource['abbreviation'];
            $class =  $resource['icon'];
            $amount  = $possessions[ $resource['id'] ]['amount'];
            if ($amount > 0):
                $xhtml .= '<a data-placement="bottom" rel="tooltip" href="#" data-original-title="'.$this->view->translate($name).'">';
                $xhtml .= '<i class="'.$class.'">'.$abbreviation.'</i> '.$amount.'</a> ';
            endif;
        endforeach;
        $xhtml .= '</div>';
        return $xhtml;
    }
}
