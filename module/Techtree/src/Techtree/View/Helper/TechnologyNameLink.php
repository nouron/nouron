<?php
namespace Techtree\View\Helper;

use Zend\View\Helper\AbstractHelper;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class TechnologyNameLink extends AbstractHelper implements ServiceLocatorAwareInterface
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
    public function __invoke($techId, $colonyId = null)
    {
        $sm = $this->getServiceLocator();
        \Zend\Debug\Debug::dump($sm->getRegisteredServices());
        $gateway = $sm->get('Techtree\Service\BuildingService');
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