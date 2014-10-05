<?php
namespace GalaxyTest\Table;

use NouronTest\Table\AbstractTableTest;
use PHPUnit_Framework_TestCase;
use GalaxyTest\Bootstrap;
use Galaxy\Table\SystemTableFactory;

class SystemTableFactoryTest extends AbstractTableTest
{
    public function setUp()
    {
        $this->initDatabaseAdapter();

        $this->sm = Bootstrap::getServiceManager();
        $this->sm->setAllowOverride(true);
        $this->sm->setService(
            'Galaxy\Entity\System',
            new \Galaxy\Entity\System()
        );
        $this->sm->setService(
            'Zend\Db\Adapter\Adapter',
            $this->dbAdapter
        );
    }

    public function testCreateService()
    {
        $tableFactory = new SystemTableFactory();
        $this->assertInstanceOf(
            'Galaxy\Table\SystemTable',
            $tableFactory->createService($this->sm)
        );
    }

}