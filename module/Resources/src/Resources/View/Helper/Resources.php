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
     * @return Resources
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
     * @return string
     */
    public function __invoke($possessions)
    {
        $sm = $this->getServiceLocator();
        $translate = $sm->get('translate');
        $xhtml = '<div class="row resource-bar">';
        foreach ($possessions as $resId => $resource):
            $name = $resource['name'];
            $abbreviation =  $resource['abbreviation'];
            $class =  $resource['icon'];
            $amount  = $possessions[ $resource['id'] ]['amount'];
            if ($amount > 0):
                $xhtml .= '<a data-placement="bottom" rel="tooltip" href="#" data-original-title="'.$translate($name).'">';
                $xhtml .= '<i class="'.$class.'">'.$abbreviation.'</i> '.$amount.'</a> ';
            endif;
        endforeach;
        $xhtml .= '</div>';
        return $xhtml;
    }
}
