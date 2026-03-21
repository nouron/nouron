<?php
namespace Techtree\View\Helper;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class TechnologyNameLinkFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        return new TechnologyNameLink(
            $container->get('Techtree\Service\BuildingService')
        );
    }
}
