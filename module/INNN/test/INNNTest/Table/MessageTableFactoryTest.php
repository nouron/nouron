<?php
namespace INNN\Test\Table;

use CoreTest\Table\AbstractTableTest;
use PHPUnit\Framework\TestCase;
use INNNTest\Bootstrap;
use INNN\Table\MessageTableFactory;

class MessageTableFactoryTest extends AbstractTableTest
{
    public function setUp(): void
    {
        $this->initDatabaseAdapter();

        $this->sm = Bootstrap::getServiceManager();
        $this->sm->setAllowOverride(true);
        $this->sm->setService(
            'INNN\Entity\Message',
            new \INNN\Entity\Message()
        );
        $this->sm->setService(
            'Laminas\Db\Adapter\Adapter',
            $this->dbAdapter
        );
    }

    public function testCreateService()
    {
        $tableFactory = new MessageTableFactory();
        $this->assertInstanceOf(
            "INNN\Table\MessageTable",
            $tableFactory($this->sm, '', [])
        );
    }

}