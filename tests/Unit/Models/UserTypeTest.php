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

use PhalconKit\Models\Abstracts\UserTypeAbstract;
use PhalconKit\Models\Abstracts\Interfaces\UserTypeAbstractInterface;
use PhalconKit\Models\UserType;
use PhalconKit\Models\Interfaces\UserTypeInterface;

/**
 * Class UserTypeTest
 *
 * This class contains unit tests for the User class.
 */
class UserTypeTest extends \PhalconKit\Tests\Unit\AbstractUnit
{
    public UserTypeInterface $userType;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->userType = new UserType();
    }
    
    public function testInstanceOf(): void
    {
        // Model
        $this->assertInstanceOf(UserType::class, $this->userType);
        $this->assertInstanceOf(UserTypeInterface::class, $this->userType);
    
        // Abstract
        $this->assertInstanceOf(UserTypeAbstract::class, $this->userType);
        $this->assertInstanceOf(UserTypeAbstractInterface::class, $this->userType);
        
        // Phalcon Kit
        $this->assertInstanceOf(\PhalconKit\Mvc\ModelInterface::class, $this->userType);
        $this->assertInstanceOf(\PhalconKit\Mvc\Model::class, $this->userType);
        
        // Phalcon
        $this->assertInstanceOf(\Phalcon\Mvc\ModelInterface::class, $this->userType);
        $this->assertInstanceOf(\Phalcon\Mvc\Model::class, $this->userType);
    }

    public function testInitialize(): void
    {
        $this->userType->initialize();

        $this->assertSame('user_type', $this->userType->getSource());
    }

    public function testValidationShouldReturnABoolean(): void
    {
        $this->assertIsBool($this->userType->validation());
    }
    
    public function testGetId(): void
    {
        $this->assertEquals(null, $this->userType->getId());
    }
    
    public function testSetId(): void
    {
        $value = uniqid();
        $this->userType->setId($value);
        $this->assertEquals($value, $this->userType->getId());
    }

    public function testGetUuid(): void
    {
        $this->assertEquals(null, $this->userType->getUuid());
    }
    
    public function testSetUuid(): void
    {
        $value = uniqid();
        $this->userType->setUuid($value);
        $this->assertEquals($value, $this->userType->getUuid());
    }

    public function testGetUserId(): void
    {
        $this->assertEquals(null, $this->userType->getUserId());
    }
    
    public function testSetUserId(): void
    {
        $value = uniqid();
        $this->userType->setUserId($value);
        $this->assertEquals($value, $this->userType->getUserId());
    }

    public function testGetTypeId(): void
    {
        $this->assertEquals(null, $this->userType->getTypeId());
    }
    
    public function testSetTypeId(): void
    {
        $value = uniqid();
        $this->userType->setTypeId($value);
        $this->assertEquals($value, $this->userType->getTypeId());
    }

    public function testGetPosition(): void
    {
        $this->assertEquals(null, $this->userType->getPosition());
    }
    
    public function testSetPosition(): void
    {
        $value = uniqid();
        $this->userType->setPosition($value);
        $this->assertEquals($value, $this->userType->getPosition());
    }

    public function testGetDeleted(): void
    {
        $this->assertEquals(null, $this->userType->getDeleted());
    }
    
    public function testSetDeleted(): void
    {
        $value = uniqid();
        $this->userType->setDeleted($value);
        $this->assertEquals($value, $this->userType->getDeleted());
    }

    public function testGetCreatedAt(): void
    {
        $this->assertEquals('current_timestamp()', $this->userType->getCreatedAt());
    }
    
    public function testSetCreatedAt(): void
    {
        $value = uniqid();
        $this->userType->setCreatedAt($value);
        $this->assertEquals($value, $this->userType->getCreatedAt());
    }

    public function testGetCreatedBy(): void
    {
        $this->assertEquals(null, $this->userType->getCreatedBy());
    }
    
    public function testSetCreatedBy(): void
    {
        $value = uniqid();
        $this->userType->setCreatedBy($value);
        $this->assertEquals($value, $this->userType->getCreatedBy());
    }

    public function testGetUpdatedAt(): void
    {
        $this->assertEquals(null, $this->userType->getUpdatedAt());
    }
    
    public function testSetUpdatedAt(): void
    {
        $value = uniqid();
        $this->userType->setUpdatedAt($value);
        $this->assertEquals($value, $this->userType->getUpdatedAt());
    }

    public function testGetUpdatedBy(): void
    {
        $this->assertEquals(null, $this->userType->getUpdatedBy());
    }
    
    public function testSetUpdatedBy(): void
    {
        $value = uniqid();
        $this->userType->setUpdatedBy($value);
        $this->assertEquals($value, $this->userType->getUpdatedBy());
    }

    public function testGetDeletedAt(): void
    {
        $this->assertEquals(null, $this->userType->getDeletedAt());
    }
    
    public function testSetDeletedAt(): void
    {
        $value = uniqid();
        $this->userType->setDeletedAt($value);
        $this->assertEquals($value, $this->userType->getDeletedAt());
    }

    public function testGetDeletedBy(): void
    {
        $this->assertEquals(null, $this->userType->getDeletedBy());
    }
    
    public function testSetDeletedBy(): void
    {
        $value = uniqid();
        $this->userType->setDeletedBy($value);
        $this->assertEquals($value, $this->userType->getDeletedBy());
    }
    
    public function testGetColumnMapShouldBeAnArray(): void
    {
        $this->assertIsArray($this->userType->getColumnMap());
    }
}
