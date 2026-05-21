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

use PhalconKit\Models\Abstracts\UserRoleAbstract;
use PhalconKit\Models\Abstracts\Interfaces\UserRoleAbstractInterface;
use PhalconKit\Models\UserRole;
use PhalconKit\Models\Interfaces\UserRoleInterface;

/**
 * Class UserRoleTest
 *
 * This class contains unit tests for the User class.
 */
class UserRoleTest extends \PhalconKit\Tests\Unit\AbstractUnit
{
    public UserRoleInterface $userRole;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->userRole = new UserRole();
    }
    
    public function testInstanceOf(): void
    {
        // Model
        $this->assertInstanceOf(UserRole::class, $this->userRole);
        $this->assertInstanceOf(UserRoleInterface::class, $this->userRole);
    
        // Abstract
        $this->assertInstanceOf(UserRoleAbstract::class, $this->userRole);
        $this->assertInstanceOf(UserRoleAbstractInterface::class, $this->userRole);
        
        // Phalcon Kit
        $this->assertInstanceOf(\PhalconKit\Mvc\ModelInterface::class, $this->userRole);
        $this->assertInstanceOf(\PhalconKit\Mvc\Model::class, $this->userRole);
        
        // Phalcon
        $this->assertInstanceOf(\Phalcon\Mvc\ModelInterface::class, $this->userRole);
        $this->assertInstanceOf(\Phalcon\Mvc\Model::class, $this->userRole);
    }

    public function testInitialize(): void
    {
        $this->userRole->initialize();

        $this->assertSame('user_role', $this->userRole->getSource());
    }

    public function testValidationShouldReturnABoolean(): void
    {
        $this->assertIsBool($this->userRole->validation());
    }
    
    public function testGetId(): void
    {
        $this->assertEquals(null, $this->userRole->getId());
    }
    
    public function testSetId(): void
    {
        $value = uniqid();
        $this->userRole->setId($value);
        $this->assertEquals($value, $this->userRole->getId());
    }

    public function testGetUuid(): void
    {
        $this->assertEquals(null, $this->userRole->getUuid());
    }
    
    public function testSetUuid(): void
    {
        $value = uniqid();
        $this->userRole->setUuid($value);
        $this->assertEquals($value, $this->userRole->getUuid());
    }

    public function testGetUserId(): void
    {
        $this->assertEquals(null, $this->userRole->getUserId());
    }
    
    public function testSetUserId(): void
    {
        $value = uniqid();
        $this->userRole->setUserId($value);
        $this->assertEquals($value, $this->userRole->getUserId());
    }

    public function testGetRoleId(): void
    {
        $this->assertEquals(null, $this->userRole->getRoleId());
    }
    
    public function testSetRoleId(): void
    {
        $value = uniqid();
        $this->userRole->setRoleId($value);
        $this->assertEquals($value, $this->userRole->getRoleId());
    }

    public function testGetPosition(): void
    {
        $this->assertEquals(null, $this->userRole->getPosition());
    }
    
    public function testSetPosition(): void
    {
        $value = uniqid();
        $this->userRole->setPosition($value);
        $this->assertEquals($value, $this->userRole->getPosition());
    }

    public function testGetDeleted(): void
    {
        $this->assertEquals(null, $this->userRole->getDeleted());
    }
    
    public function testSetDeleted(): void
    {
        $value = uniqid();
        $this->userRole->setDeleted($value);
        $this->assertEquals($value, $this->userRole->getDeleted());
    }

    public function testGetCreatedAt(): void
    {
        $this->assertEquals('current_timestamp()', $this->userRole->getCreatedAt());
    }
    
    public function testSetCreatedAt(): void
    {
        $value = uniqid();
        $this->userRole->setCreatedAt($value);
        $this->assertEquals($value, $this->userRole->getCreatedAt());
    }

    public function testGetCreatedBy(): void
    {
        $this->assertEquals(null, $this->userRole->getCreatedBy());
    }
    
    public function testSetCreatedBy(): void
    {
        $value = uniqid();
        $this->userRole->setCreatedBy($value);
        $this->assertEquals($value, $this->userRole->getCreatedBy());
    }

    public function testGetUpdatedAt(): void
    {
        $this->assertEquals('NULL', $this->userRole->getUpdatedAt());
    }
    
    public function testSetUpdatedAt(): void
    {
        $value = uniqid();
        $this->userRole->setUpdatedAt($value);
        $this->assertEquals($value, $this->userRole->getUpdatedAt());
    }

    public function testGetUpdatedBy(): void
    {
        $this->assertEquals(null, $this->userRole->getUpdatedBy());
    }
    
    public function testSetUpdatedBy(): void
    {
        $value = uniqid();
        $this->userRole->setUpdatedBy($value);
        $this->assertEquals($value, $this->userRole->getUpdatedBy());
    }

    public function testGetDeletedAt(): void
    {
        $this->assertEquals('NULL', $this->userRole->getDeletedAt());
    }
    
    public function testSetDeletedAt(): void
    {
        $value = uniqid();
        $this->userRole->setDeletedAt($value);
        $this->assertEquals($value, $this->userRole->getDeletedAt());
    }

    public function testGetDeletedBy(): void
    {
        $this->assertEquals(null, $this->userRole->getDeletedBy());
    }
    
    public function testSetDeletedBy(): void
    {
        $value = uniqid();
        $this->userRole->setDeletedBy($value);
        $this->assertEquals($value, $this->userRole->getDeletedBy());
    }
    
    public function testGetColumnMapShouldBeAnArray(): void
    {
        $this->assertIsArray($this->userRole->getColumnMap());
    }
}
