<?php
namespace TechtreeTest\Service;

use PHPUnit_Framework_TestCase;
use TechtreeTest\Bootstrap;

class ResearchServiceFactoryTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->sm = Bootstrap::getServiceManager();
        $this->sm->setAllowOverride(true);

        $this->sm->setService(
            'Nouron\Service\Tick',
            $this->getMockBuilder('Nouron\Service\Tick')
                  ->disableOriginalConstructor()
                  ->getMock()
        );

        $this->sm->setService(
            'logger',
            $this->getMockBuilder('Zend\Log\Logger')
                  ->disableOriginalConstructor()
                  ->getMock()
        );

        $this->sm->setService(
            'Techtree\Table\ResearchTable',
            $this->getMockBuilder('Techtree\Table\ResearchTable')
                 ->disableOriginalConstructor()
                 ->getMock()
        );

        $this->sm->setService(
            'Techtree\Table\ResearchCostTable',
            $this->getMockBuilder('Techtree\Table\ResearchCostTable')
                 ->disableOriginalConstructor()
                 ->getMock()
        );

        $this->sm->setService(
            'Techtree\Table\ColonyResearchTable',
            $this->getMockBuilder('Techtree\Table\ColonyResearchTable')
                 ->disableOriginalConstructor()
                 ->getMock()
        );

        $this->sm->setService(
            'Resources\Service\ResourcesService',
            $this->getMockBuilder('Resources\Service\ResourcesService')
                 ->disableOriginalConstructor()
                 ->getMock()
        );

        $this->sm->setService(
            'Galaxy\Service\Gateway',
            $this->getMockBuilder('Galaxy\Service\Gateway')
                 ->disableOriginalConstructor()
                 ->getMock()
        );
    }

    public function testCreateService()
    {
        $this->assertInstanceOf(
            "Techtree\Service\ResearchService",
            $this->sm->get('Techtree\Service\ResearchService')
        );
    }
}