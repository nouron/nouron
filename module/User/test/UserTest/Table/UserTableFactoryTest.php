<?php
namespace UserTest\Table;

use PHPUnit\Framework\TestCase;
use UserTest\Bootstrap;
use User\Table\UserTableFactory;

class UserTableFactoryTest extends TestCase
{
    public function setUp(): void
    {
        $this->sm = Bootstrap::getServiceManager();
    }

    public function testCreateService()
    {
        $factory = new UserTableFactory();
        $this->assertInstanceOf(
            'User\Table\UserTable',
            $factory($this->sm, '', [])
        );
    }
}
