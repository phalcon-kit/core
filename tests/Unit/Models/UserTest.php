<?php

/**
 * This file is part of the Phalcon Kit.
 *
 * (c) Phalcon Kit Team
 *
 * For the full copyright and license information, please view the LICENSE.txt
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PhalconKit\Tests\Unit\Models;

use PhalconKit\Models\Abstracts\UserAbstract;
use PhalconKit\Models\Abstracts\Interfaces\UserAbstractInterface;
use PhalconKit\Models\User;
use PhalconKit\Models\Interfaces\UserInterface;

/**
 * Class UserTest
 *
 * This class contains unit tests for the User class.
 */
class UserTest extends \PhalconKit\Tests\Unit\AbstractUnit
{
    public UserInterface $user;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->user = new User();
    }
    
    public function testInstanceOf(): void
    {
        // Model
        $this->assertInstanceOf(User::class, $this->user);
        $this->assertInstanceOf(UserInterface::class, $this->user);
    
        // Abstract
        $this->assertInstanceOf(UserAbstract::class, $this->user);
        $this->assertInstanceOf(UserAbstractInterface::class, $this->user);
        
        // Phalcon Kit
        $this->assertInstanceOf(\PhalconKit\Mvc\ModelInterface::class, $this->user);
        $this->assertInstanceOf(\PhalconKit\Mvc\Model::class, $this->user);
        
        // Phalcon
        $this->assertInstanceOf(\Phalcon\Mvc\ModelInterface::class, $this->user);
        $this->assertInstanceOf(\Phalcon\Mvc\Model::class, $this->user);
    }

    public function testInitialize(): void
    {
        $this->user->initialize();

        $this->assertSame('user', $this->user->getSource());
    }

    public function testValidationShouldReturnABoolean(): void
    {
        $this->assertIsBool($this->user->validation());
    }
    
    public function testGetId(): void
    {
        $this->assertEquals(null, $this->user->getId());
    }
    
    public function testSetId(): void
    {
        $value = uniqid();
        $this->user->setId($value);
        $this->assertEquals($value, $this->user->getId());
    }

    public function testGetUuid(): void
    {
        $this->assertEquals(null, $this->user->getUuid());
    }
    
    public function testSetUuid(): void
    {
        $value = uniqid();
        $this->user->setUuid($value);
        $this->assertEquals($value, $this->user->getUuid());
    }

    public function testGetEmail(): void
    {
        $this->assertEquals(null, $this->user->getEmail());
    }
    
    public function testSetEmail(): void
    {
        $value = uniqid();
        $this->user->setEmail($value);
        $this->assertEquals($value, $this->user->getEmail());
    }

    public function testGetPassword(): void
    {
        $this->assertEquals('NULL', $this->user->getPassword());
    }
    
    public function testSetPassword(): void
    {
        $value = uniqid();
        $this->user->setPassword($value);
        $this->assertEquals($value, $this->user->getPassword());
    }

    public function testGetResetToken(): void
    {
        $this->assertEquals('NULL', $this->user->getResetToken());
    }
    
    public function testSetResetToken(): void
    {
        $value = uniqid();
        $this->user->setResetToken($value);
        $this->assertEquals($value, $this->user->getResetToken());
    }

    public function testGetDeleted(): void
    {
        $this->assertEquals(null, $this->user->getDeleted());
    }
    
    public function testSetDeleted(): void
    {
        $value = uniqid();
        $this->user->setDeleted($value);
        $this->assertEquals($value, $this->user->getDeleted());
    }

    public function testGetCreatedAt(): void
    {
        $this->assertEquals('current_timestamp()', $this->user->getCreatedAt());
    }
    
    public function testSetCreatedAt(): void
    {
        $value = uniqid();
        $this->user->setCreatedAt($value);
        $this->assertEquals($value, $this->user->getCreatedAt());
    }

    public function testGetCreatedBy(): void
    {
        $this->assertEquals(null, $this->user->getCreatedBy());
    }
    
    public function testSetCreatedBy(): void
    {
        $value = uniqid();
        $this->user->setCreatedBy($value);
        $this->assertEquals($value, $this->user->getCreatedBy());
    }

    public function testGetUpdatedAt(): void
    {
        $this->assertEquals('NULL', $this->user->getUpdatedAt());
    }
    
    public function testSetUpdatedAt(): void
    {
        $value = uniqid();
        $this->user->setUpdatedAt($value);
        $this->assertEquals($value, $this->user->getUpdatedAt());
    }

    public function testGetUpdatedBy(): void
    {
        $this->assertEquals(null, $this->user->getUpdatedBy());
    }
    
    public function testSetUpdatedBy(): void
    {
        $value = uniqid();
        $this->user->setUpdatedBy($value);
        $this->assertEquals($value, $this->user->getUpdatedBy());
    }

    public function testGetDeletedAt(): void
    {
        $this->assertEquals('NULL', $this->user->getDeletedAt());
    }
    
    public function testSetDeletedAt(): void
    {
        $value = uniqid();
        $this->user->setDeletedAt($value);
        $this->assertEquals($value, $this->user->getDeletedAt());
    }

    public function testGetDeletedBy(): void
    {
        $this->assertEquals(null, $this->user->getDeletedBy());
    }
    
    public function testSetDeletedBy(): void
    {
        $value = uniqid();
        $this->user->setDeletedBy($value);
        $this->assertEquals($value, $this->user->getDeletedBy());
    }
    
    public function testGetColumnMapShouldBeAnArray(): void
    {
        $this->assertIsArray($this->user->getColumnMap());
    }
}
