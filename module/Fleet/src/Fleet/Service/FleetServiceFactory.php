<?php
namespace Fleet\Service;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Fleet\Entity\Fleet;
use Fleet\Entity\FleetShip;
use Fleet\Entity\FleetPersonell;
use Fleet\Entity\FleetResearch;
use Fleet\Entity\FleetResource;
use Fleet\Entity\FleetOrder;

use Fleet\Table\FleetTable;
use Fleet\Table\FleetShipTable;
use Fleet\Table\FleetPersonellTable;
use Fleet\Table\FleetResearchTable;
use Fleet\Table\FleetResourceTable;
use Fleet\Table\FleetOrderTable;

use Techtree\Entity\ColonyShip;
use Techtree\Entity\ColonyPersonell;
use Techtree\Entity\ColonyResearch;
#use Techtree\Entity\ColonyResource;
use Techtree\Table\ColonyShipTable;
use Techtree\Table\ColonyPersonellTable;
use Techtree\Table\ColonyResearchTable;
use Resources\Table\ColonyResourceTable;
use Resources\Entity\Colony as ColonyResource;

class FleetServiceFactory implements FactoryInterface
{
    /**
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @return
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $db     = $serviceLocator->get('Zend\Db\Adapter\Adapter');
        $tick   = $serviceLocator->get('Nouron\Service\Tick');
        $logger = $serviceLocator->get('logger');

        $tables['fleet']     = new \Fleet\Table\FleetTable($db, new Fleet());
        $tables['fleetship'] = new \Fleet\Table\FleetShipTable($db, new FleetShip());
        $tables['fleetpersonell'] = new \Fleet\Table\FleetPersonellTable($db, new FleetPersonell());
        $tables['fleetresearch']  = new \Fleet\Table\FleetResearchTable($db, new FleetResearch());
        $tables['fleetorder']     = new \Fleet\Table\FleetOrderTable($db, new FleetOrder());
        $tables['fleetresource']  = new \Fleet\Table\FleetResourceTable($db, new FleetResource());

        $tables['colonyship']      = new \Techtree\Table\ColonyShipTable($db, new ColonyShip());
        $tables['colonypersonell'] = new \Techtree\Table\ColonyPersonellTable($db, new ColonyPersonell());
        $tables['colonyresearch']  = new \Techtree\Table\ColonyResearchTable($db, new ColonyResearch());
        $tables['colonyresource']  = new \Resources\Table\ColonyTable($db, new ColonyResource());

        $service = new FleetService($tick, $tables, array());
        $service->setLogger($logger);
        if (isset($_SESSION['fleetId'])) {
            $service->setFleetId($_SESSION['fleet_id']);
        }
        return $service;
    }
}