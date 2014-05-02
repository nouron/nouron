<?php
namespace TechtreeTest\Service;

use PHPUnit_Framework_TestCase;
use TechtreeTest\Bootstrap;
use Techtree\Entity\BuildingFactory;

class BuildingFactoryTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->sm = Bootstrap::getServiceManager();
        $this->sm->setAllowOverride(true);

        $servicesToMock = array(
            'Zend\Db\Adapter\Adapter' => 'Zend\Db\Adapter\Adapter'
            #'Techtree\Entity\Building' => 'Techtree\Entity\Building'
        );
        foreach ($servicesToMock as $key => $serviceName) {
            $this->sm->setService(
                $key,
                $this->getMockBuilder($serviceName)
                     ->disableOriginalConstructor()
                     ->getMock()
            );
        }

        $this->sm->setFactory('Techtree\Entity\BuildingFactory', 'Techtree\Entity\BuildingFactory');

    }

    public function testCreateService()
    {
        $this->assertInstanceOf(
            "Techtree\Entity\Building",
            $this->sm->get('Techtree\Entity\Building')
        );
    }

}