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

use PhalconKit\Models\Abstracts\RoleAbstract;
use PhalconKit\Models\Abstracts\Interfaces\RoleAbstractInterface;
use PhalconKit\Models\Role;
use PhalconKit\Models\Interfaces\RoleInterface;

/**
 * Class RoleTest
 *
 * This class contains unit tests for the User class.
 */
class RoleTest extends \PhalconKit\Tests\Unit\AbstractUnit
{
    public RoleInterface $role;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->role = new Role();
    }
    
    public function testInstanceOf(): void
    {
        // Model
        $this->assertInstanceOf(Role::class, $this->role);
        $this->assertInstanceOf(RoleInterface::class, $this->role);
    
        // Abstract
        $this->assertInstanceOf(RoleAbstract::class, $this->role);
        $this->assertInstanceOf(RoleAbstractInterface::class, $this->role);
        
        // Phalcon Kit
        $this->assertInstanceOf(\PhalconKit\Mvc\ModelInterface::class, $this->role);
        $this->assertInstanceOf(\PhalconKit\Mvc\Model::class, $this->role);
        
        // Phalcon
        $this->assertInstanceOf(\Phalcon\Mvc\ModelInterface::class, $this->role);
        $this->assertInstanceOf(\Phalcon\Mvc\Model::class, $this->role);
    }

    public function testInitialize(): void
    {
        $this->role->initialize();

        $this->assertSame('role', $this->role->getSource());
    }

    public function testValidationShouldReturnABoolean(): void
    {
        $this->assertIsBool($this->role->validation());
    }
    
    public function testGetId(): void
    {
        $this->assertEquals(null, $this->role->getId());
    }
    
    public function testSetId(): void
    {
        $value = uniqid();
        $this->role->setId($value);
        $this->assertEquals($value, $this->role->getId());
    }

    public function testGetUuid(): void
    {
        $this->assertEquals(null, $this->role->getUuid());
    }
    
    public function testSetUuid(): void
    {
        $value = uniqid();
        $this->role->setUuid($value);
        $this->assertEquals($value, $this->role->getUuid());
    }

    public function testGetKey(): void
    {
        $this->assertEquals(null, $this->role->getKey());
    }
    
    public function testSetKey(): void
    {
        $value = uniqid();
        $this->role->setKey($value);
        $this->assertEquals($value, $this->role->getKey());
    }

    public function testGetLabel(): void
    {
        $this->assertEquals(null, $this->role->getLabel());
    }
    
    public function testSetLabel(): void
    {
        $value = uniqid();
        $this->role->setLabel($value);
        $this->assertEquals($value, $this->role->getLabel());
    }

    public function testGetPosition(): void
    {
        $this->assertEquals(null, $this->role->getPosition());
    }
    
    public function testSetPosition(): void
    {
        $value = uniqid();
        $this->role->setPosition($value);
        $this->assertEquals($value, $this->role->getPosition());
    }

    public function testGetDeleted(): void
    {
        $this->assertEquals(null, $this->role->getDeleted());
    }
    
    public function testSetDeleted(): void
    {
        $value = uniqid();
        $this->role->setDeleted($value);
        $this->assertEquals($value, $this->role->getDeleted());
    }

    public function testGetCreatedAt(): void
    {
        $this->assertEquals('current_timestamp()', $this->role->getCreatedAt());
    }
    
    public function testSetCreatedAt(): void
    {
        $value = uniqid();
        $this->role->setCreatedAt($value);
        $this->assertEquals($value, $this->role->getCreatedAt());
    }

    public function testGetCreatedBy(): void
    {
        $this->assertEquals(null, $this->role->getCreatedBy());
    }
    
    public function testSetCreatedBy(): void
    {
        $value = uniqid();
        $this->role->setCreatedBy($value);
        $this->assertEquals($value, $this->role->getCreatedBy());
    }

    public function testGetUpdatedAt(): void
    {
        $this->assertEquals(null, $this->role->getUpdatedAt());
    }
    
    public function testSetUpdatedAt(): void
    {
        $value = uniqid();
        $this->role->setUpdatedAt($value);
        $this->assertEquals($value, $this->role->getUpdatedAt());
    }

    public function testGetUpdatedBy(): void
    {
        $this->assertEquals(null, $this->role->getUpdatedBy());
    }
    
    public function testSetUpdatedBy(): void
    {
        $value = uniqid();
        $this->role->setUpdatedBy($value);
        $this->assertEquals($value, $this->role->getUpdatedBy());
    }

    public function testGetDeletedAt(): void
    {
        $this->assertEquals(null, $this->role->getDeletedAt());
    }
    
    public function testSetDeletedAt(): void
    {
        $value = uniqid();
        $this->role->setDeletedAt($value);
        $this->assertEquals($value, $this->role->getDeletedAt());
    }

    public function testGetDeletedBy(): void
    {
        $this->assertEquals(null, $this->role->getDeletedBy());
    }
    
    public function testSetDeletedBy(): void
    {
        $value = uniqid();
        $this->role->setDeletedBy($value);
        $this->assertEquals($value, $this->role->getDeletedBy());
    }
    
    public function testGetColumnMapShouldBeAnArray(): void
    {
        $this->assertIsArray($this->role->getColumnMap());
    }
}
