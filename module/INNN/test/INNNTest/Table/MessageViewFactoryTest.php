<?php
namespace INNN\Test\Table;

use NouronTest\Table\AbstractTableTest;
use PHPUnit_Framework_TestCase;
use INNNTest\Bootstrap;
use INNN\Table\MessageViewFactory;

class MessageViewFactoryTest extends AbstractTableTest
{
    public function setUp()
    {
        $this->initDatabaseAdapter();

        $this->sm = Bootstrap::getServiceManager();
        $this->sm->setAllowOverride(true);
        $this->sm->setService(
            'INNN\Entity\Message',
            new \INNN\Entity\Message()
        );
        $this->sm->setService(
            'Zend\Db\Adapter\Adapter',
            $this->dbAdapter
        );
    }

    public function testCreateService()
    {
        $tableFactory = new MessageViewFactory();
        $this->assertInstanceOf(
            "INNN\Table\MessageView",
            $tableFactory->createService($this->sm)
        );
    }

}