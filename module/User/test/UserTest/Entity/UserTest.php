<?php
namespace UserTest\Entity;

use PHPUnit\Framework\TestCase;
use User\Entity\User;

class UserTest extends TestCase
{
    /**
     * @var User
     */
    protected $entity;

    public function setUp(): void
    {
        $this->entity = new User();
    }

    public function testInitialStateIsNull()
    {
        $this->assertNull($this->entity->getId());
        $this->assertNull($this->entity->getUsername());
        $this->assertNull($this->entity->getEmail());
        $this->assertNull($this->entity->getDisplayName());
        $this->assertNull($this->entity->getPassword());
        $this->assertNull($this->entity->getState());
    }

    public function testSetGetId()
    {
        $result = $this->entity->setId(42);
        $this->assertSame($this->entity, $result); // fluent interface
        $this->assertSame(42, $this->entity->getId());
    }

    public function testSetIdCastsToInt()
    {
        $this->entity->setId('7');
        $this->assertSame(7, $this->entity->getId());
    }

    public function testSetGetUsername()
    {
        $result = $this->entity->setUsername('Homer');
        $this->assertSame($this->entity, $result);
        $this->assertSame('Homer', $this->entity->getUsername());
    }

    public function testSetGetEmail()
    {
        $result = $this->entity->setEmail('homer@nouron.de');
        $this->assertSame($this->entity, $result);
        $this->assertSame('homer@nouron.de', $this->entity->getEmail());
    }

    public function testSetGetDisplayName()
    {
        $result = $this->entity->setDisplayName('Homer Simpson');
        $this->assertSame($this->entity, $result);
        $this->assertSame('Homer Simpson', $this->entity->getDisplayName());
    }

    public function testSetGetPassword()
    {
        $hash = '$2y$14$nFApucgOokdP66.LfoskBO';
        $result = $this->entity->setPassword($hash);
        $this->assertSame($this->entity, $result);
        $this->assertSame($hash, $this->entity->getPassword());
    }

    public function testSetGetState()
    {
        $result = $this->entity->setState(1);
        $this->assertSame($this->entity, $result);
        $this->assertSame(1, $this->entity->getState());
    }

    public function testSetGetRole()
    {
        $this->entity->setRole('admin');
        $this->assertSame('admin', $this->entity->getRole());
    }

    public function testImplementsEntityInterface()
    {
        $this->assertInstanceOf('Core\Entity\EntityInterface', $this->entity);
    }

    public function testImplementsUserInterface()
    {
        $this->assertInstanceOf('LmcUser\Entity\UserInterface', $this->entity);
    }
}
