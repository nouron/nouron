<?php
namespace INNN\Test\Table;

use CoreTest\Table\AbstractTableTest;
use PHPUnit\Framework\TestCase;
use INNNTest\Bootstrap;
use INNN\Table\MessageViewFactory;

class MessageViewFactoryTest extends AbstractTableTest
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
        $tableFactory = new MessageViewFactory();
        $this->assertInstanceOf(
            "INNN\Table\MessageView",
            $tableFactory($this->sm, '', [])
        );
    }

}