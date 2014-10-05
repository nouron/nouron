<?php
namespace GalaxyTest\Table;

use NouronTest\Table\AbstractTableTest;
use PHPUnit_Framework_TestCase;
use GalaxyTest\Bootstrap;
use Galaxy\Table\SystemObjectTableFactory;

class SystemObjectTableFactoryTest extends AbstractTableTest
{
    public function setUp()
    {
        $this->initDatabaseAdapter();

        $this->sm = Bootstrap::getServiceManager();
        $this->sm->setAllowOverride(true);
        $this->sm->setService(
            'Galaxy\Entity\SystemObject',
            new \Galaxy\Entity\SystemObject()
        );
        $this->sm->setService(
            'Zend\Db\Adapter\Adapter',
            $this->dbAdapter
        );
    }

    public function testCreateService()
    {
        $tableFactory = new SystemObjectTableFactory();
        $this->assertInstanceOf(
            'Galaxy\Table\SystemObjectTable',
            $tableFactory->createService($this->sm)
        );
    }

}