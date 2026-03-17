<?php
namespace UserTest\Service;

use PHPUnit\Framework\TestCase;
use UserTest\Bootstrap;
use User\Service\UserServiceFactory;

class UserServiceFactoryTest extends TestCase
{
    public function setUp(): void
    {
        $this->sm = Bootstrap::getServiceManager();
    }

    public function testCreateService()
    {
        $factory = new UserServiceFactory();
        $this->assertInstanceOf(
            'User\Service\UserService',
            $factory($this->sm, '', [])
        );
    }
}
