<?php
namespace Resources\View\Helper;

use Zend\View\Helper\AbstractHelper;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class Resources extends AbstractHelper implements ServiceLocatorAwareInterface
{
    /**
     * Set the service locator.
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @return TechnologyNameLink
     */
    public function setServiceLocator(ServiceLocatorInterface $serviceLocator)
    {
        $this->serviceLocator = $serviceLocator;
        return $this;
    }
    /**
     * Get the service locator.
     *
     * @return \Zend\ServiceManager\ServiceLocatorInterface
     */
    public function getServiceLocator()
    {
        return $this->serviceLocator;
    }

    /**
     * @param  int $techId
     * @param  int $colonyId OPTIONAL
     * @return string
     */
    public function __invoke()
    {
        $sm = $this->getServiceLocator();

//          $galaxyGw = $sm->get('Galaxy\Service\Gateway');
//          $resourcesGw = $sm->get('Resources\Service\Gateway');
//          $colony = $galaxyGw->getCurrentColony();
//          $possessions = $resourcesGw->getPossessionsByColonyId($colony['id']);

        $xhtml = '<div class="row-fluid resource-bar">';

        //     foreach ($possessions as $resource):
        //         $name = $resource['name'];
        //         $abbreviation =  $resource['abbreviation'];
        //         $class =  $resource['icon'];
        //         $amount  = $possessions[ $resource['id'] ]['amount'];
        //         if ($amount > 0):
        //             echo '<a data-placement="bottom" rel="tooltip" href="#" data-original-title="'.$name.'">';
        //             echo '<i class="'.$class.'">'.$abbreviation.'</i> '.$amount.'</a> ';
        //         endif;
        //     endforeach;
        $xhtml .= '<a data-placement="bottom" rel="tooltip" href="#" data-original-title="Ticks"><i class="icon-time"></i> 5 </a> ';
        $xhtml .= '<a data-placement="bottom" rel="tooltip" href="#" data-original-title="Credits"><i class="resicon-credits">Cr</i> 123456 </a> ';
        $xhtml .= '<a data-placement="bottom" rel="tooltip" href="#" data-original-title="Supply"><i class="resicon-supply">Sup</i> 155/322 </a> ';
        $xhtml .= '<a data-placement="bottom" rel="tooltip" href="#" data-original-title="Moral"><i class="resicon-moral">Mor</i> gut </a> ';
        $xhtml .= '<a data-placement="bottom" rel="tooltip" href="#" data-original-title="Eisen"><i class="resicon-iron">I</i> 12345 </a> ';
        $xhtml .= '<a data-placement="bottom" rel="tooltip" href="#" data-original-title="Wasser"><i class="resicon-water">W</i> 12345 </a> ';
        $xhtml .= '<a data-placement="bottom" rel="tooltip" href="#" data-original-title="Silikate"><i class="resicon-silicates">S</i> 12345 </a> ';
        $xhtml .= '<a data-placement="bottom" rel="tooltip" href="#" data-original-title=""><i class="resicon-ena">E</i> 12345 </a> ';
        $xhtml .= '<a data-placement="bottom" rel="tooltip" href="#" data-original-title=""><i class="resicon-aku">A</i> 12345 </a> ';
        $xhtml .= '<a data-placement="bottom" rel="tooltip" href="#" data-original-title=""><i class="resicon-lho">L</i> 12345 </a> ';
        $xhtml .= '</div>';

        return $xhtml;
    }
}