<?php
namespace FleetTest\Service;

use PHPUnit_Framework_TestCase;
use TechtreeTest\Bootstrap;
use Fleet\Service\FleetServiceFactory;
#use Fleet\Entity\Fleet;
#use Fleet\Entity\FleetShip;
#use Fleet\Entity\FleetPersonell;
#use Fleet\Entity\FleetResearch;
#use Fleet\Entity\FleetResource;
#use Fleet\Entity\FleetOrder;
#use Fleet\Table\FleetTable;
#use Fleet\Table\FleetShipTable;
#use Fleet\Table\FleetPersonellTable;
#use Fleet\Table\FleetResearchTable;
#use Fleet\Table\FleetResourceTable;
#use Fleet\Table\FleetOrderTable;
#use Galaxy\Entity\System;
#use Galaxy\Entity\Colony;
#use Galaxy\Table\SystemTable;
#use Galaxy\Table\ColonyTable;
#use Techtree\Entity\ColonyShip;
#use Techtree\Entity\ColonyPersonell;
#use Techtree\Entity\ColonyResearch;
##use Techtree\Entity\ColonyResource;
#use Techtree\Table\ColonyShipTable;
#use Techtree\Table\ColonyPersonellTable;
#use Techtree\Table\ColonyResearchTable;
##use Resources\Table\ColonyResourceTable;
#use Resources\Entity\Colony as ColonyResource;

class FleetServiceFactoryTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->sm = Bootstrap::getServiceManager();
        $this->sm->setAllowOverride(true);

        $servicesToMock = array(
            'Zend\Db\Adapter\Adapter' => 'Zend\Db\Adapter\Adapter',
            'Nouron\Service\Tick' => 'Nouron\Service\Tick',
            'logger' => 'Zend\Log\Logger',
            'Galaxy\Table\ColonyTable' => 'Galaxy\Table\ColonyTable',
            'Galaxy\Table\SystemTable' => 'Galaxy\Table\SystemTable',
            'Fleet\Table\FleetTable' => 'Fleet\Table\FleetTable',
            'Fleet\Table\FleetTable' => 'Fleet\Table\FleetTable',
            'Fleet\Table\FleetShipTable' => 'Fleet\Table\FleetShipTable',
            'Fleet\Table\FleetPersonellTable' => 'Fleet\Table\FleetPersonellTable',
            'Fleet\Table\FleetResearchTable' => 'Fleet\Table\FleetResearchTable',
            'Fleet\Table\FleetOrderTable' => 'Fleet\Table\FleetOrderTable',
            'Fleet\Table\FleetResourceTable' => 'Fleet\Table\FleetResourceTable',

            'Techtree\Table\ColonyShipTable' => 'Techtree\Table\ColonyShipTable',
            'Techtree\Table\ColonyPersonellTable' => 'Techtree\Table\ColonyPersonellTable',
            'Techtree\Table\ColonyResearchTable' => 'Techtree\Table\ColonyResearchTable',
            'Resources\Table\ColonyTable' => 'Resources\Table\ColonyTable'
        );
        foreach ($servicesToMock as $key => $serviceName) {
            $this->sm->setService(
                $key,
                $this->getMockBuilder($serviceName)
                     ->disableOriginalConstructor()
                     ->getMock()
            );
        }
    }

    public function testCreateService()
    {
        $factory = new FleetServiceFactory();
        $entity  = $factory->createService($this->sm);

        $this->assertInstanceOf(
            "Fleet\Service\FleetService",
            $entity
        );
    }
}