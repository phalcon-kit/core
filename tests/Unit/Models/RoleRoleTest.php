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

use PhalconKit\Models\Abstracts\RoleRoleAbstract;
use PhalconKit\Models\Abstracts\Interfaces\RoleRoleAbstractInterface;
use PhalconKit\Models\RoleRole;
use PhalconKit\Models\Interfaces\RoleRoleInterface;

/**
 * Class RoleRoleTest
 *
 * This class contains unit tests for the User class.
 */
class RoleRoleTest extends \PhalconKit\Tests\Unit\AbstractUnit
{
    public RoleRoleInterface $roleRole;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->roleRole = new RoleRole();
    }
    
    public function testInstanceOf(): void
    {
        // Model
        $this->assertInstanceOf(RoleRole::class, $this->roleRole);
        $this->assertInstanceOf(RoleRoleInterface::class, $this->roleRole);
    
        // Abstract
        $this->assertInstanceOf(RoleRoleAbstract::class, $this->roleRole);
        $this->assertInstanceOf(RoleRoleAbstractInterface::class, $this->roleRole);
        
        // Phalcon Kit
        $this->assertInstanceOf(\PhalconKit\Mvc\ModelInterface::class, $this->roleRole);
        $this->assertInstanceOf(\PhalconKit\Mvc\Model::class, $this->roleRole);
        
        // Phalcon
        $this->assertInstanceOf(\Phalcon\Mvc\ModelInterface::class, $this->roleRole);
        $this->assertInstanceOf(\Phalcon\Mvc\Model::class, $this->roleRole);
    }

    public function testInitialize(): void
    {
        $this->roleRole->initialize();

        $this->assertSame('role_role', $this->roleRole->getSource());
    }

    public function testValidationShouldReturnABoolean(): void
    {
        $this->assertIsBool($this->roleRole->validation());
    }
    
    public function testGetId(): void
    {
        $this->assertEquals(null, $this->roleRole->getId());
    }
    
    public function testSetId(): void
    {
        $value = uniqid();
        $this->roleRole->setId($value);
        $this->assertEquals($value, $this->roleRole->getId());
    }

    public function testGetUuid(): void
    {
        $this->assertEquals(null, $this->roleRole->getUuid());
    }
    
    public function testSetUuid(): void
    {
        $value = uniqid();
        $this->roleRole->setUuid($value);
        $this->assertEquals($value, $this->roleRole->getUuid());
    }

    public function testGetParentId(): void
    {
        $this->assertEquals(null, $this->roleRole->getParentId());
    }
    
    public function testSetParentId(): void
    {
        $value = uniqid();
        $this->roleRole->setParentId($value);
        $this->assertEquals($value, $this->roleRole->getParentId());
    }

    public function testGetChildId(): void
    {
        $this->assertEquals(null, $this->roleRole->getChildId());
    }
    
    public function testSetChildId(): void
    {
        $value = uniqid();
        $this->roleRole->setChildId($value);
        $this->assertEquals($value, $this->roleRole->getChildId());
    }

    public function testGetPosition(): void
    {
        $this->assertEquals(null, $this->roleRole->getPosition());
    }
    
    public function testSetPosition(): void
    {
        $value = uniqid();
        $this->roleRole->setPosition($value);
        $this->assertEquals($value, $this->roleRole->getPosition());
    }

    public function testGetDeleted(): void
    {
        $this->assertEquals(null, $this->roleRole->getDeleted());
    }
    
    public function testSetDeleted(): void
    {
        $value = uniqid();
        $this->roleRole->setDeleted($value);
        $this->assertEquals($value, $this->roleRole->getDeleted());
    }

    public function testGetCreatedAt(): void
    {
        $this->assertEquals('current_timestamp()', $this->roleRole->getCreatedAt());
    }
    
    public function testSetCreatedAt(): void
    {
        $value = uniqid();
        $this->roleRole->setCreatedAt($value);
        $this->assertEquals($value, $this->roleRole->getCreatedAt());
    }

    public function testGetCreatedBy(): void
    {
        $this->assertEquals(null, $this->roleRole->getCreatedBy());
    }
    
    public function testSetCreatedBy(): void
    {
        $value = uniqid();
        $this->roleRole->setCreatedBy($value);
        $this->assertEquals($value, $this->roleRole->getCreatedBy());
    }
    
    public function testGetColumnMapShouldBeAnArray(): void
    {
        $this->assertIsArray($this->roleRole->getColumnMap());
    }
}
