<?php
namespace UserTest\Service;

use CoreTest\Service\AbstractServiceTest;
use User\Service\UserService;
use User\Table\UserTable;
use User\Entity\User;

class UserServiceTest extends AbstractServiceTest
{
    public function setUp(): void
    {
        $this->initDatabaseAdapter();
        $this->initDatabase();

        $tables = array();
        $tables['user'] = new UserTable($this->dbAdapter, new User());

        $tick = new \Core\Service\Tick(['calculation' => ['start' => 3, 'end' => 4]], 1234);

        $this->_service = new UserService($tick, $tables);

        // Test usernames from Simpson test data
        $this->_existingUsername  = 'Homer';
        $this->_existingUserId    = 0;
        $this->_anotherUsername   = 'Bart';
        $this->_anotherUserId     = 3;
    }

    public function testGetUserByName()
    {
        // test positive — Homer exists with id=0
        $user = $this->_service->getUserByName($this->_existingUsername);
        $this->assertInstanceOf('User\Entity\User', $user);
        $this->assertSame($this->_existingUserId, $user->getId());
        $this->assertSame($this->_existingUsername, $user->getUsername());

        // test another user — Bart exists with id=3
        $user = $this->_service->getUserByName($this->_anotherUsername);
        $this->assertInstanceOf('User\Entity\User', $user);
        $this->assertSame($this->_anotherUserId, $user->getId());
        $this->assertSame($this->_anotherUsername, $user->getUsername());

        // test negative — nonexistent user returns falsy
        $user = $this->_service->getUserByName('NonExistentUser99');
        $this->assertFalse($user);
    }

    public function testGetUserByNameReturnsEmail()
    {
        $user = $this->_service->getUserByName($this->_existingUsername);
        $this->assertSame('homer@nouron.de', $user->getEmail());
    }
}
