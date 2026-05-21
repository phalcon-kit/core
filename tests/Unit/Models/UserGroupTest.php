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

use PhalconKit\Models\Abstracts\UserGroupAbstract;
use PhalconKit\Models\Abstracts\Interfaces\UserGroupAbstractInterface;
use PhalconKit\Models\UserGroup;
use PhalconKit\Models\Interfaces\UserGroupInterface;

/**
 * Class UserGroupTest
 *
 * This class contains unit tests for the User class.
 */
class UserGroupTest extends \PhalconKit\Tests\Unit\AbstractUnit
{
    public UserGroupInterface $userGroup;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->userGroup = new UserGroup();
    }
    
    public function testInstanceOf(): void
    {
        // Model
        $this->assertInstanceOf(UserGroup::class, $this->userGroup);
        $this->assertInstanceOf(UserGroupInterface::class, $this->userGroup);
    
        // Abstract
        $this->assertInstanceOf(UserGroupAbstract::class, $this->userGroup);
        $this->assertInstanceOf(UserGroupAbstractInterface::class, $this->userGroup);
        
        // Phalcon Kit
        $this->assertInstanceOf(\PhalconKit\Mvc\ModelInterface::class, $this->userGroup);
        $this->assertInstanceOf(\PhalconKit\Mvc\Model::class, $this->userGroup);
        
        // Phalcon
        $this->assertInstanceOf(\Phalcon\Mvc\ModelInterface::class, $this->userGroup);
        $this->assertInstanceOf(\Phalcon\Mvc\Model::class, $this->userGroup);
    }

    public function testInitialize(): void
    {
        $this->userGroup->initialize();

        $this->assertSame('user_group', $this->userGroup->getSource());
    }

    public function testValidationShouldReturnABoolean(): void
    {
        $this->assertIsBool($this->userGroup->validation());
    }
    
    public function testGetId(): void
    {
        $this->assertEquals(null, $this->userGroup->getId());
    }
    
    public function testSetId(): void
    {
        $value = uniqid();
        $this->userGroup->setId($value);
        $this->assertEquals($value, $this->userGroup->getId());
    }

    public function testGetUuid(): void
    {
        $this->assertEquals(null, $this->userGroup->getUuid());
    }
    
    public function testSetUuid(): void
    {
        $value = uniqid();
        $this->userGroup->setUuid($value);
        $this->assertEquals($value, $this->userGroup->getUuid());
    }

    public function testGetUserId(): void
    {
        $this->assertEquals(null, $this->userGroup->getUserId());
    }
    
    public function testSetUserId(): void
    {
        $value = uniqid();
        $this->userGroup->setUserId($value);
        $this->assertEquals($value, $this->userGroup->getUserId());
    }

    public function testGetGroupId(): void
    {
        $this->assertEquals(null, $this->userGroup->getGroupId());
    }
    
    public function testSetGroupId(): void
    {
        $value = uniqid();
        $this->userGroup->setGroupId($value);
        $this->assertEquals($value, $this->userGroup->getGroupId());
    }

    public function testGetPosition(): void
    {
        $this->assertEquals(null, $this->userGroup->getPosition());
    }
    
    public function testSetPosition(): void
    {
        $value = uniqid();
        $this->userGroup->setPosition($value);
        $this->assertEquals($value, $this->userGroup->getPosition());
    }

    public function testGetDeleted(): void
    {
        $this->assertEquals(null, $this->userGroup->getDeleted());
    }
    
    public function testSetDeleted(): void
    {
        $value = uniqid();
        $this->userGroup->setDeleted($value);
        $this->assertEquals($value, $this->userGroup->getDeleted());
    }

    public function testGetCreatedAt(): void
    {
        $this->assertEquals('current_timestamp()', $this->userGroup->getCreatedAt());
    }
    
    public function testSetCreatedAt(): void
    {
        $value = uniqid();
        $this->userGroup->setCreatedAt($value);
        $this->assertEquals($value, $this->userGroup->getCreatedAt());
    }

    public function testGetCreatedBy(): void
    {
        $this->assertEquals(null, $this->userGroup->getCreatedBy());
    }
    
    public function testSetCreatedBy(): void
    {
        $value = uniqid();
        $this->userGroup->setCreatedBy($value);
        $this->assertEquals($value, $this->userGroup->getCreatedBy());
    }

    public function testGetUpdatedAt(): void
    {
        $this->assertEquals('NULL', $this->userGroup->getUpdatedAt());
    }
    
    public function testSetUpdatedAt(): void
    {
        $value = uniqid();
        $this->userGroup->setUpdatedAt($value);
        $this->assertEquals($value, $this->userGroup->getUpdatedAt());
    }

    public function testGetUpdatedBy(): void
    {
        $this->assertEquals(null, $this->userGroup->getUpdatedBy());
    }
    
    public function testSetUpdatedBy(): void
    {
        $value = uniqid();
        $this->userGroup->setUpdatedBy($value);
        $this->assertEquals($value, $this->userGroup->getUpdatedBy());
    }

    public function testGetDeletedAt(): void
    {
        $this->assertEquals('NULL', $this->userGroup->getDeletedAt());
    }
    
    public function testSetDeletedAt(): void
    {
        $value = uniqid();
        $this->userGroup->setDeletedAt($value);
        $this->assertEquals($value, $this->userGroup->getDeletedAt());
    }

    public function testGetDeletedBy(): void
    {
        $this->assertEquals(null, $this->userGroup->getDeletedBy());
    }
    
    public function testSetDeletedBy(): void
    {
        $value = uniqid();
        $this->userGroup->setDeletedBy($value);
        $this->assertEquals($value, $this->userGroup->getDeletedBy());
    }
    
    public function testGetColumnMapShouldBeAnArray(): void
    {
        $this->assertIsArray($this->userGroup->getColumnMap());
    }
}
