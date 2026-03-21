<?php
namespace UserTest\Entity;

use PHPUnit\Framework\TestCase;
use UserTest\Bootstrap;
use User\Entity\UserFactory;

class UserFactoryTest extends TestCase
{
    public function setUp(): void
    {
        $this->sm = Bootstrap::getServiceManager();
    }

    public function testCreateService()
    {
        $factory = new UserFactory();
        $this->assertInstanceOf(
            'User\Entity\User',
            $factory($this->sm, '', [])
        );
    }
}
