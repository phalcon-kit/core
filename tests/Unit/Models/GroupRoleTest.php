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

use PhalconKit\Models\Abstracts\GroupRoleAbstract;
use PhalconKit\Models\Abstracts\Interfaces\GroupRoleAbstractInterface;
use PhalconKit\Models\GroupRole;
use PhalconKit\Models\Interfaces\GroupRoleInterface;

/**
 * Class GroupRoleTest
 *
 * This class contains unit tests for the User class.
 */
class GroupRoleTest extends \PhalconKit\Tests\Unit\AbstractUnit
{
    public GroupRoleInterface $groupRole;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->groupRole = new GroupRole();
    }
    
    public function testInstanceOf(): void
    {
        // Model
        $this->assertInstanceOf(GroupRole::class, $this->groupRole);
        $this->assertInstanceOf(GroupRoleInterface::class, $this->groupRole);
    
        // Abstract
        $this->assertInstanceOf(GroupRoleAbstract::class, $this->groupRole);
        $this->assertInstanceOf(GroupRoleAbstractInterface::class, $this->groupRole);
        
        // Phalcon Kit
        $this->assertInstanceOf(\PhalconKit\Mvc\ModelInterface::class, $this->groupRole);
        $this->assertInstanceOf(\PhalconKit\Mvc\Model::class, $this->groupRole);
        
        // Phalcon
        $this->assertInstanceOf(\Phalcon\Mvc\ModelInterface::class, $this->groupRole);
        $this->assertInstanceOf(\Phalcon\Mvc\Model::class, $this->groupRole);
    }

    public function testInitialize(): void
    {
        $this->groupRole->initialize();

        $this->assertSame('group_role', $this->groupRole->getSource());
    }

    public function testValidationShouldReturnABoolean(): void
    {
        $this->assertIsBool($this->groupRole->validation());
    }
    
    public function testGetId(): void
    {
        $this->assertEquals(null, $this->groupRole->getId());
    }
    
    public function testSetId(): void
    {
        $value = uniqid();
        $this->groupRole->setId($value);
        $this->assertEquals($value, $this->groupRole->getId());
    }

    public function testGetUuid(): void
    {
        $this->assertEquals(null, $this->groupRole->getUuid());
    }
    
    public function testSetUuid(): void
    {
        $value = uniqid();
        $this->groupRole->setUuid($value);
        $this->assertEquals($value, $this->groupRole->getUuid());
    }

    public function testGetGroupId(): void
    {
        $this->assertEquals(null, $this->groupRole->getGroupId());
    }
    
    public function testSetGroupId(): void
    {
        $value = uniqid();
        $this->groupRole->setGroupId($value);
        $this->assertEquals($value, $this->groupRole->getGroupId());
    }

    public function testGetRoleId(): void
    {
        $this->assertEquals(null, $this->groupRole->getRoleId());
    }
    
    public function testSetRoleId(): void
    {
        $value = uniqid();
        $this->groupRole->setRoleId($value);
        $this->assertEquals($value, $this->groupRole->getRoleId());
    }

    public function testGetPosition(): void
    {
        $this->assertEquals(null, $this->groupRole->getPosition());
    }
    
    public function testSetPosition(): void
    {
        $value = uniqid();
        $this->groupRole->setPosition($value);
        $this->assertEquals($value, $this->groupRole->getPosition());
    }

    public function testGetDeleted(): void
    {
        $this->assertEquals(null, $this->groupRole->getDeleted());
    }
    
    public function testSetDeleted(): void
    {
        $value = uniqid();
        $this->groupRole->setDeleted($value);
        $this->assertEquals($value, $this->groupRole->getDeleted());
    }

    public function testGetCreatedAt(): void
    {
        $this->assertEquals('current_timestamp()', $this->groupRole->getCreatedAt());
    }
    
    public function testSetCreatedAt(): void
    {
        $value = uniqid();
        $this->groupRole->setCreatedAt($value);
        $this->assertEquals($value, $this->groupRole->getCreatedAt());
    }

    public function testGetCreatedBy(): void
    {
        $this->assertEquals(null, $this->groupRole->getCreatedBy());
    }
    
    public function testSetCreatedBy(): void
    {
        $value = uniqid();
        $this->groupRole->setCreatedBy($value);
        $this->assertEquals($value, $this->groupRole->getCreatedBy());
    }
    
    public function testGetColumnMapShouldBeAnArray(): void
    {
        $this->assertIsArray($this->groupRole->getColumnMap());
    }
}
