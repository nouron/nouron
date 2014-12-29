<?php
namespace GalaxyTest\Table;

use CoreTest\Table\AbstractTableTest;
use PHPUnit_Framework_TestCase;
use GalaxyTest\Bootstrap;
use Galaxy\Table\ColonyTableFactory;

class ColonyTableFactoryTest extends AbstractTableTest
{
    public function setUp()
    {
        $this->initDatabaseAdapter();

        $this->sm = Bootstrap::getServiceManager();
        $this->sm->setAllowOverride(true);
        $this->sm->setService(
            'Galaxy\Entity\Colony',
            new \Galaxy\Entity\Colony()
        );
        $this->sm->setService(
            'Zend\Db\Adapter\Adapter',
            $this->dbAdapter
        );
    }

    public function testCreateService()
    {
        $tableFactory = new ColonyTableFactory();
        $this->assertInstanceOf(
            'Galaxy\Table\ColonyTable',
            $tableFactory->createService($this->sm)
        );
    }

}